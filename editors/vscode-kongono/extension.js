'use strict';

const vscode = require('vscode');
const cp = require('child_process');
const fs = require('fs');
const path = require('path');

const MAX_BUFFER_BYTES = 16 * 1024 * 1024;
const MAX_DOC_BYTES = 2 * 1024 * 1024;
const REQUEST_TIMEOUT_MS = 10_000;
const CHANGE_DEBOUNCE_MS = 250;

/**
 * Check whether `targetPath` resolves cleanly inside `rootDir` (case-insensitive on Windows).
 *
 * @param {string} targetPath
 * @param {string} rootDir
 * @returns {boolean}
 */
function isPathInside(targetPath, rootDir) {
  try {
    const realRoot = fs.realpathSync(rootDir);
    const realTarget = fs.realpathSync(targetPath);
    const normRoot = (process.platform === 'win32' ? realRoot.toLowerCase() : realRoot).replace(/[\\/]+$/, '');
    const normTarget = process.platform === 'win32' ? realTarget.toLowerCase() : realTarget;
    return normTarget === normRoot || normTarget.startsWith(normRoot + path.sep);
  } catch (_) {
    return false;
  }
}

/**
 * Resolve the local Kongono PHP CLI entry script inside a workspace without following escaping symlinks.
 *
 * @param {string} workspaceRoot
 * @returns {string|null}
 */
function resolveKongoBin(workspaceRoot) {
  const candidates = [
    path.join(workspaceRoot, 'vendor', 'kongono', 'framework', 'bin', 'kongo'),
    path.join(workspaceRoot, 'vendor', 'kongono', 'cli', 'bin', 'kongo'),
    path.join(workspaceRoot, 'bin', 'kongo'),
    path.join(workspaceRoot, 'vendor', 'bin', 'kongo'),
  ];
  for (const candidate of candidates) {
    try {
      const stat = fs.lstatSync(candidate);
      if (stat.isSymbolicLink() && !isPathInside(candidate, workspaceRoot)) {
        continue;
      }
      if (fs.statSync(candidate).isFile() && isPathInside(candidate, workspaceRoot)) {
        return candidate;
      }
    } catch (_) {
      // candidate absent or unreadable
    }
  }
  return null;
}

/**
 * Resolve configured `kongono.appPath` relative to `workspaceRoot` when non-empty.
 *
 * @param {string} workspaceRoot
 * @param {string} rawAppPath
 * @returns {string}
 */
function resolveAppPath(workspaceRoot, rawAppPath) {
  const trimmed = String(rawAppPath || '').trim();
  if (!trimmed) return workspaceRoot;
  return path.isAbsolute(trimmed) ? trimmed : path.resolve(workspaceRoot, trimmed);
}

/**
 * Map an LSP 3.17 DiagnosticSeverity integer to VS Code's DiagnosticSeverity enum.
 *
 * @param {number|undefined} severity
 */
function mapSeverity(severity) {
  switch (severity) {
    case 1:
      return vscode.DiagnosticSeverity.Error;
    case 2:
      return vscode.DiagnosticSeverity.Warning;
    case 3:
      return vscode.DiagnosticSeverity.Information;
    case 4:
      return vscode.DiagnosticSeverity.Hint;
    default:
      return vscode.DiagnosticSeverity.Warning;
  }
}

/**
 * Resolve the most relevant workspace root for a document or active editor.
 *
 * @param {vscode.Uri|undefined} [uri]
 * @returns {string|null}
 */
function resolveWorkspaceRoot(uri) {
  if (uri) {
    const folder = vscode.workspace.getWorkspaceFolder(uri);
    if (folder) return folder.uri.fsPath;
  }
  const active = vscode.window.activeTextEditor;
  if (active) {
    const folder = vscode.workspace.getWorkspaceFolder(active.document.uri);
    if (folder) return folder.uri.fsPath;
  }
  const folders = vscode.workspace.workspaceFolders;
  return folders && folders.length ? folders[0].uri.fsPath : null;
}

/**
 * Zero-dependency VS Code / Cursor / Windsurf / Antigravity bridge for Kongono (`kongo lsp`).
 *
 * Keeps 100% of framework intelligence inside Kongono itself — this thin client merely streams
 * LSP 3.17 Content-Length JSON-RPC frames between the editor and `kongo lsp`.
 */
class KongonoLspClient {
  constructor(output, diagnostics) {
    this.output = output;
    this.diagnostics = diagnostics;
    this.proc = null;
    this.workspaceRoot = null;
    this.appRoot = null;
    this.buffer = Buffer.alloc(0);
    this.nextId = 1;
    this.pending = new Map();
    this.changeTimers = new Map();
    this.ready = false;
  }

  resolveKongoBin(workspaceRoot) {
    return resolveKongoBin(workspaceRoot);
  }

  rejectAllPending(reason) {
    for (const entry of this.pending.values()) {
      clearTimeout(entry.timer);
      entry.reject(reason);
    }
    this.pending.clear();
  }

  clearChangeTimers() {
    for (const timer of this.changeTimers.values()) {
      clearTimeout(timer);
    }
    this.changeTimers.clear();
  }

  isManagedDocument(doc) {
    if (!isSupportedDoc(doc)) return false;
    const scopeRoot = this.appRoot || this.workspaceRoot;
    if (!scopeRoot) return false;
    return isPathInside(doc.fileName, scopeRoot);
  }

  start(workspaceRoot) {
    this.stop();
    if (!workspaceRoot) return;
    if (!vscode.workspace.isTrusted) {
      this.output.appendLine('[kongono] Workspace is not trusted; refusing to execute local PHP/kongo binaries.');
      return;
    }
    const cfg = vscode.workspace.getConfiguration('kongono');
    if (!cfg.get('enableLsp', true)) return;

    const phpBin = String(cfg.get('phpPath', 'php') || 'php').trim() || 'php';
    const appPath = resolveAppPath(workspaceRoot, cfg.get('appPath', ''));
    const kongoBin = this.resolveKongoBin(workspaceRoot);
    if (!kongoBin) {
      this.output.appendLine('[kongono] No vendor/bin/kongo or bin/kongo found in ' + workspaceRoot);
      return;
    }

    this.workspaceRoot = workspaceRoot;
    this.appRoot = appPath;

    const args = [kongoBin, 'lsp', '--app=' + appPath];
    this.output.appendLine('[kongono] Starting LSP: ' + phpBin + ' ' + args.join(' '));
    const proc = cp.spawn(phpBin, args, {
      cwd: workspaceRoot,
      stdio: ['pipe', 'pipe', 'pipe'],
      windowsHide: true,
    });
    this.proc = proc;

    proc.on('error', (err) => {
      if (this.proc !== proc) return;
      this.output.appendLine('[kongono] Failed to spawn PHP LSP process: ' + err.message);
      this.ready = false;
      this.proc = null;
      this.rejectAllPending(err);
    });

    proc.stdout.on('data', (chunk) => {
      if (this.proc !== proc) return;
      this.onData(chunk);
    });

    proc.stderr.on('data', (chunk) => {
      if (this.proc !== proc) return;
      const text = chunk.toString('utf8').trim();
      if (text) {
        this.output.appendLine('[kongono stderr] ' + text);
      }
    });

    proc.on('exit', (code) => {
      if (this.proc !== proc) return;
      this.output.appendLine('[kongono] LSP exited with code ' + code);
      this.ready = false;
      this.proc = null;
      this.rejectAllPending(new Error('Kongono LSP stopped'));
    });

    this.request('initialize', {
      processId: process.pid,
      rootUri: vscode.Uri.file(workspaceRoot).toString(),
      capabilities: {},
    }).then(() => {
      if (this.proc !== proc) return;
      this.ready = true;
      this.notify('initialized', {});
      for (const doc of vscode.workspace.textDocuments) {
        if (this.isManagedDocument(doc)) this.didOpen(doc);
      }
    }).catch((err) => {
      if (this.proc !== proc) return;
      this.output.appendLine('[kongono] Initialize failed: ' + err.message);
    });
  }

  stop() {
    this.clearChangeTimers();
    const proc = this.proc;
    this.proc = null;
    this.workspaceRoot = null;
    this.appRoot = null;
    this.ready = false;
    this.buffer = Buffer.alloc(0);
    this.rejectAllPending(new Error('Kongono LSP stopped'));
    if (proc) {
      try {
        if (proc.stdin && proc.stdin.writable) {
          const shutdownMsg = JSON.stringify({ jsonrpc: '2.0', id: this.nextId++, method: 'shutdown', params: null });
          const exitMsg = JSON.stringify({ jsonrpc: '2.0', method: 'exit', params: null });
          proc.stdin.write(
            'Content-Length: ' + Buffer.byteLength(shutdownMsg, 'utf8') + '\r\n\r\n' + shutdownMsg +
            'Content-Length: ' + Buffer.byteLength(exitMsg, 'utf8') + '\r\n\r\n' + exitMsg,
            'utf8',
          );
          proc.stdin.end();
        }
      } catch (_) {
        // ignore write errors during shutdown
      }
      setTimeout(() => {
        try {
          if (proc.exitCode === null && !proc.killed) {
            proc.kill();
          }
        } catch (_) {
          // ignore
        }
      }, 300).unref();
    }
    this.diagnostics.clear();
  }

  sendRaw(payload) {
    if (!this.proc || !this.proc.stdin || !this.proc.stdin.writable) return false;
    const json = JSON.stringify(payload);
    const bytes = Buffer.byteLength(json, 'utf8');
    this.proc.stdin.write('Content-Length: ' + bytes + '\r\n\r\n' + json, 'utf8');
    return true;
  }

  notify(method, params) {
    this.sendRaw({ jsonrpc: '2.0', method, params });
  }

  request(method, params, timeoutMs = REQUEST_TIMEOUT_MS) {
    const id = this.nextId++;
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => {
        if (this.pending.has(id)) {
          this.pending.delete(id);
          reject(new Error('Kongono LSP request timed out: ' + method));
        }
      }, timeoutMs);
      timer.unref();
      this.pending.set(id, { resolve, reject, timer });
      if (!this.sendRaw({ jsonrpc: '2.0', id, method, params })) {
        clearTimeout(timer);
        this.pending.delete(id);
        reject(new Error('Kongono LSP process is not writable'));
      }
    });
  }

  didOpen(doc) {
    if (!this.ready || !this.isManagedDocument(doc)) return;
    const text = doc.getText();
    if (Buffer.byteLength(text, 'utf8') > MAX_DOC_BYTES) return;
    this.notify('textDocument/didOpen', {
      textDocument: {
        uri: doc.uri.toString(),
        languageId: doc.languageId,
        version: doc.version,
        text,
      },
    });
  }

  didChange(doc) {
    if (!this.ready || !this.isManagedDocument(doc)) return;
    const key = doc.uri.toString();
    const prev = this.changeTimers.get(key);
    if (prev) clearTimeout(prev);
    const timer = setTimeout(() => {
      this.changeTimers.delete(key);
      if (!this.ready) return;
      const text = doc.getText();
      if (Buffer.byteLength(text, 'utf8') > MAX_DOC_BYTES) return;
      this.notify('textDocument/didChange', {
        textDocument: { uri: key, version: doc.version },
        contentChanges: [{ text }],
      });
    }, CHANGE_DEBOUNCE_MS);
    timer.unref();
    this.changeTimers.set(key, timer);
  }

  didSave(doc) {
    if (!this.ready || !this.isManagedDocument(doc)) return;
    const key = doc.uri.toString();
    const prev = this.changeTimers.get(key);
    if (prev) {
      clearTimeout(prev);
      this.changeTimers.delete(key);
    }
    const text = doc.getText();
    if (Buffer.byteLength(text, 'utf8') > MAX_DOC_BYTES) return;
    this.notify('textDocument/didSave', {
      textDocument: { uri: key },
      text,
    });
  }

  didClose(doc) {
    const key = doc.uri.toString();
    const prev = this.changeTimers.get(key);
    if (prev) {
      clearTimeout(prev);
      this.changeTimers.delete(key);
    }
    if (!this.ready) return;
    this.notify('textDocument/didClose', {
      textDocument: { uri: key },
    });
  }

  onData(chunk) {
    if (this.buffer.length + chunk.length > MAX_BUFFER_BYTES) {
      this.output.appendLine('[kongono] LSP buffer exceeded 16 MiB cap; stopping client.');
      this.stop();
      return;
    }
    this.buffer = Buffer.concat([this.buffer, chunk]);
    while (true) {
      const sep = this.buffer.indexOf('\r\n\r\n');
      if (sep === -1) break;
      const header = this.buffer.subarray(0, sep).toString('ascii');
      const match = /Content-Length:\s*(\d+)/i.exec(header);
      if (!match) {
        this.buffer = this.buffer.subarray(sep + 4);
        continue;
      }
      const len = parseInt(match[1], 10);
      if (!Number.isFinite(len) || len < 0 || len > MAX_BUFFER_BYTES) {
        this.output.appendLine('[kongono] Invalid LSP Content-Length frame; dropping buffer.');
        this.buffer = Buffer.alloc(0);
        break;
      }
      const total = sep + 4 + len;
      if (this.buffer.length < total) break;
      const body = this.buffer.subarray(sep + 4, total).toString('utf8');
      this.buffer = this.buffer.subarray(total);
      try {
        this.onMessage(JSON.parse(body));
      } catch (err) {
        this.output.appendLine('[kongono] Bad JSON frame: ' + err.message);
      }
    }
  }

  onMessage(msg) {
    if (!msg || typeof msg !== 'object') return;
    if (msg.id !== undefined && this.pending.has(msg.id)) {
      const entry = this.pending.get(msg.id);
      this.pending.delete(msg.id);
      clearTimeout(entry.timer);
      if (msg.error) {
        entry.reject(new Error(msg.error.message || 'Kongono LSP RPC error'));
      } else {
        entry.resolve(msg.result);
      }
      return;
    }
    if (msg.method === 'textDocument/publishDiagnostics' && msg.params && typeof msg.params.uri === 'string') {
      const uri = vscode.Uri.parse(msg.params.uri);
      const rawList = Array.isArray(msg.params.diagnostics) ? msg.params.diagnostics : [];
      const list = [];
      for (const d of rawList) {
        if (!d || !d.range || !d.range.start || !d.range.end || typeof d.message !== 'string') {
          continue;
        }
        const range = new vscode.Range(
          Math.max(0, Number(d.range.start.line) || 0),
          Math.max(0, Number(d.range.start.character) || 0),
          Math.max(0, Number(d.range.end.line) || 0),
          Math.max(0, Number(d.range.end.character) || 0),
        );
        const diag = new vscode.Diagnostic(range, d.message, mapSeverity(d.severity));
        if (d.code !== undefined) diag.code = d.code;
        diag.source = typeof d.source === 'string' && d.source ? d.source : 'Kongono';
        list.push(diag);
      }
      this.diagnostics.set(uri, list);
    }
  }
}

function isSupportedDoc(doc) {
  if (!doc || !doc.uri || doc.uri.scheme !== 'file' || typeof doc.fileName !== 'string') {
    return false;
  }
  const normalized = doc.fileName.replace(/\\/g, '/').toLowerCase();
  if (
    normalized.includes('/vendor/') ||
    normalized.includes('/node_modules/') ||
    normalized.includes('/.git/') ||
    normalized.includes('/var/cache/')
  ) {
    return false;
  }
  return (
    doc.languageId === 'kongono-template' ||
    doc.languageId === 'php' ||
    normalized.endsWith('.kgn.html') ||
    normalized.endsWith('.php')
  );
}

function runTerminalCommand(workspaceRoot, argsList) {
  if (!vscode.workspace.isTrusted) {
    vscode.window.showWarningMessage('Kongono commands require a trusted workspace.');
    return;
  }
  const cfg = vscode.workspace.getConfiguration('kongono');
  const phpBin = String(cfg.get('phpPath', 'php') || 'php').trim() || 'php';
  const rawAppPath = String(cfg.get('appPath', '') || '').trim();
  const kongoBin = resolveKongoBin(workspaceRoot);
  if (!kongoBin) {
    vscode.window.showErrorMessage('Could not find vendor/bin/kongo or bin/kongo in ' + workspaceRoot);
    return;
  }
  const fullArgs = [kongoBin, ...argsList];
  if (rawAppPath) {
    fullArgs.push('--app=' + resolveAppPath(workspaceRoot, rawAppPath));
  }
  const term = vscode.window.createTerminal({
    name: 'Kongono',
    cwd: workspaceRoot,
    shellPath: phpBin,
    shellArgs: fullArgs,
  });
  term.show();
}

function activate(context) {
  const output = vscode.window.createOutputChannel('Kongono');
  const diagnostics = vscode.languages.createDiagnosticCollection('kongono');
  const client = new KongonoLspClient(output, diagnostics);

  const startIfAvailable = () => {
    const root = resolveWorkspaceRoot();
    if (!root || !vscode.workspace.isTrusted) {
      client.stop();
      return;
    }
    client.start(root);
  };

  startIfAvailable();

  const selector = [
    { language: 'kongono-template', scheme: 'file' },
    { language: 'php', scheme: 'file' },
  ];

  context.subscriptions.push(
    output,
    diagnostics,
    vscode.workspace.onDidGrantWorkspaceTrust(() => startIfAvailable()),
    vscode.workspace.onDidChangeWorkspaceFolders(() => startIfAvailable()),
    vscode.workspace.onDidChangeConfiguration((ev) => {
      if (ev.affectsConfiguration('kongono')) {
        startIfAvailable();
      }
    }),
    vscode.workspace.onDidOpenTextDocument((doc) => { if (client.isManagedDocument(doc)) client.didOpen(doc); }),
    vscode.workspace.onDidChangeTextDocument((ev) => { if (client.isManagedDocument(ev.document)) client.didChange(ev.document); }),
    vscode.workspace.onDidSaveTextDocument((doc) => { if (client.isManagedDocument(doc)) client.didSave(doc); }),
    vscode.workspace.onDidCloseTextDocument((doc) => { if (isSupportedDoc(doc)) client.didClose(doc); }),
    vscode.languages.registerHoverProvider(selector, {
      async provideHover(doc, pos) {
        if (!client.ready || !client.isManagedDocument(doc)) return null;
        try {
          const res = await client.request('textDocument/hover', {
            textDocument: { uri: doc.uri.toString() },
            position: { line: pos.line, character: pos.character },
          });
          if (!res || !res.contents || typeof res.contents.value !== 'string' || !res.contents.value) {
            return null;
          }
          const md = new vscode.MarkdownString(res.contents.value);
          md.isTrusted = false;
          return new vscode.Hover(md);
        } catch (_) {
          return null;
        }
      },
    }),
    vscode.languages.registerCodeActionsProvider(selector, {
      async provideCodeActions(doc, range, ctx) {
        if (!client.ready || !client.isManagedDocument(doc)) return [];
        const scopeRoot = client.appRoot || client.workspaceRoot;
        if (!scopeRoot) return [];
        try {
          const diagPayload = (ctx && Array.isArray(ctx.diagnostics) ? ctx.diagnostics : []).map((d) => ({
            range: {
              start: { line: d.range.start.line, character: d.range.start.character },
              end: { line: d.range.end.line, character: d.range.end.character },
            },
            message: d.message,
            code: d.code,
            source: d.source,
          }));
          const res = await client.request('textDocument/codeAction', {
            textDocument: { uri: doc.uri.toString() },
            range: {
              start: { line: range.start.line, character: range.start.character },
              end: { line: range.end.line, character: range.end.character },
            },
            context: { diagnostics: diagPayload },
          });
          if (!Array.isArray(res)) return [];
          const actions = [];
          for (const a of res) {
            if (!a || typeof a.title !== 'string') continue;
            const action = new vscode.CodeAction(a.title, vscode.CodeActionKind.QuickFix);
            action.isPreferred = Boolean(a.isPreferred);
            const edit = new vscode.WorkspaceEdit();
            const changes = (a.edit && a.edit.changes) || {};
            let editCount = 0;
            for (const [uriStr, edits] of Object.entries(changes)) {
              if (!Array.isArray(edits)) continue;
              const targetUri = vscode.Uri.parse(uriStr);
              if (targetUri.scheme !== 'file' || !isPathInside(targetUri.fsPath, scopeRoot)) {
                continue;
              }
              for (const e of edits) {
                if (!e || !e.range || !e.range.start || !e.range.end || typeof e.newText !== 'string') continue;
                edit.replace(
                  targetUri,
                  new vscode.Range(e.range.start.line, e.range.start.character, e.range.end.line, e.range.end.character),
                  e.newText,
                );
                editCount++;
              }
            }
            if (editCount > 0) {
              action.edit = edit;
              actions.push(action);
            }
          }
          return actions;
        } catch (_) {
          return [];
        }
      },
    }, { providedCodeActionKinds: [vscode.CodeActionKind.QuickFix] }),
    vscode.commands.registerCommand('kongono.prove', () => {
      const root = resolveWorkspaceRoot();
      if (root) runTerminalCommand(root, ['prove']);
    }),
    vscode.commands.registerCommand('kongono.diagnose', () => {
      const root = resolveWorkspaceRoot();
      if (root) runTerminalCommand(root, ['diagnose']);
    }),
    vscode.commands.registerCommand('kongono.autofix', () => {
      const root = resolveWorkspaceRoot();
      if (root) runTerminalCommand(root, ['diagnose', '--fix']);
    }),
    vscode.commands.registerCommand('kongono.restartLsp', () => startIfAvailable()),
    { dispose: () => client.stop() },
  );
}

function deactivate() {}

module.exports = {
  activate,
  deactivate,
  KongonoLspClient,
  isSupportedDoc,
  isPathInside,
  resolveKongoBin,
  resolveAppPath,
  mapSeverity,
};
