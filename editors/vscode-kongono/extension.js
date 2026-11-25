'use strict';

const vscode = require('vscode');
const cp = require('child_process');
const fs = require('fs');
const path = require('path');

/**
 * Zero-dependency VS Code / Cursor / Windsurf bridge for Kongono (`php vendor/bin/kongo lsp`).
 *
 * Keeps 100% of framework intelligence inside Kongono itself — this thin client merely streams
 * LSP 3.17 Content-Length JSON-RPC frames between the editor and `kongo lsp`.
 */
class KongonoLspClient {
  constructor(output, diagnostics) {
    this.output = output;
    this.diagnostics = diagnostics;
    this.proc = null;
    this.buffer = Buffer.alloc(0);
    this.nextId = 1;
    this.pending = new Map();
    this.ready = false;
  }

  resolveKongoBin(workspaceRoot) {
    const candidates = [
      path.join(workspaceRoot, 'vendor', 'bin', 'kongo'),
      path.join(workspaceRoot, 'bin', 'kongo'),
    ];
    for (const c of candidates) {
      if (fs.existsSync(c)) return c;
    }
    return null;
  }

  start(workspaceRoot) {
    this.stop();
    if (!vscode.workspace.isTrusted) {
      this.output.appendLine('[kongono] Workspace is not trusted; refusing to execute local PHP/kongo binaries.');
      return;
    }
    const cfg = vscode.workspace.getConfiguration('kongono');
    if (!cfg.get('enableLsp', true)) return;

    const phpBin = cfg.get('phpPath', 'php') || 'php';
    const appPath = cfg.get('appPath', '') || workspaceRoot;
    const kongoBin = this.resolveKongoBin(workspaceRoot);
    if (!kongoBin) {
      this.output.appendLine('[kongono] No vendor/bin/kongo or bin/kongo found in ' + workspaceRoot);
      return;
    }

    const args = [kongoBin, 'lsp', '--app=' + appPath];
    this.output.appendLine('[kongono] Starting LSP: ' + phpBin + ' ' + args.join(' '));
    this.proc = cp.spawn(phpBin, args, { cwd: workspaceRoot, stdio: ['pipe', 'pipe', 'pipe'] });

    this.proc.on('error', (err) => {
      this.output.appendLine('[kongono] Failed to spawn PHP LSP process: ' + err.message);
      this.ready = false;
      this.proc = null;
      for (const [, reject] of this.pending.values()) {
        reject(err);
      }
      this.pending.clear();
    });
    this.proc.stdout.on('data', (chunk) => this.onData(chunk));
    this.proc.stderr.on('data', (chunk) => this.output.appendLine('[kongono stderr] ' + chunk.toString('utf8').trim()));
    this.proc.on('exit', (code) => {
      this.output.appendLine('[kongono] LSP exited with code ' + code);
      this.ready = false;
      this.proc = null;
      for (const [, reject] of this.pending.values()) {
        reject(new Error('Kongono LSP stopped'));
      }
      this.pending.clear();
    });

    this.request('initialize', {
      processId: process.pid,
      rootUri: vscode.Uri.file(workspaceRoot).toString(),
      capabilities: {},
    }).then(() => {
      this.ready = true;
      this.notify('initialized', {});
      for (const doc of vscode.workspace.textDocuments) {
        if (isSupportedDoc(doc)) this.didOpen(doc);
      }
    }).catch((err) => {
      this.output.appendLine('[kongono] Initialize failed: ' + err.message);
    });
  }

  stop() {
    if (this.proc) {
      try {
        this.notify('exit', null);
        this.proc.kill();
      } catch (_) {
        // ignore
      }
      this.proc = null;
    }
    this.ready = false;
    this.buffer = Buffer.alloc(0);
    this.diagnostics.clear();
  }

  sendRaw(payload) {
    if (!this.proc || !this.proc.stdin.writable) return;
    const json = JSON.stringify(payload);
    const bytes = Buffer.byteLength(json, 'utf8');
    this.proc.stdin.write('Content-Length: ' + bytes + '\r\n\r\n' + json, 'utf8');
  }

  notify(method, params) {
    this.sendRaw({ jsonrpc: '2.0', method, params });
  }

  request(method, params) {
    const id = this.nextId++;
    return new Promise((resolve, reject) => {
      this.pending.set(id, [resolve, reject]);
      this.sendRaw({ jsonrpc: '2.0', id, method, params });
    });
  }

  didOpen(doc) {
    if (!this.ready) return;
    this.notify('textDocument/didOpen', {
      textDocument: {
        uri: doc.uri.toString(),
        languageId: doc.languageId,
        version: doc.version,
        text: doc.getText(),
      },
    });
  }

  didChange(doc) {
    if (!this.ready) return;
    this.notify('textDocument/didChange', {
      textDocument: { uri: doc.uri.toString(), version: doc.version },
      contentChanges: [{ text: doc.getText() }],
    });
  }

  didSave(doc) {
    if (!this.ready) return;
    this.notify('textDocument/didSave', {
      textDocument: { uri: doc.uri.toString() },
      text: doc.getText(),
    });
  }

  didClose(doc) {
    if (!this.ready) return;
    this.notify('textDocument/didClose', {
      textDocument: { uri: doc.uri.toString() },
    });
  }

  onData(chunk) {
    const MAX_BUFFER_BYTES = 16 * 1024 * 1024;
    if (this.buffer.length + chunk.length > MAX_BUFFER_BYTES) {
      this.output.appendLine('[kongono] LSP buffer exceeded 16 MiB cap; restarting client.');
      this.stop();
      return;
    }
    this.buffer = Buffer.concat([this.buffer, chunk]);
    while (true) {
      const sep = this.buffer.indexOf('\r\n\r\n');
      if (sep === -1) break;
      const header = this.buffer.slice(0, sep).toString('ascii');
      const match = /Content-Length:\s*(\d+)/i.exec(header);
      if (!match) {
        this.buffer = this.buffer.slice(sep + 4);
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
      const body = this.buffer.slice(sep + 4, total).toString('utf8');
      this.buffer = this.buffer.slice(total);
      try {
        this.onMessage(JSON.parse(body));
      } catch (err) {
        this.output.appendLine('[kongono] Bad JSON frame: ' + err.message);
      }
    }
  }

  onMessage(msg) {
    if (msg.id !== undefined && this.pending.has(msg.id)) {
      const [resolve, reject] = this.pending.get(msg.id);
      this.pending.delete(msg.id);
      if (msg.error) {
        reject(new Error(msg.error.message || 'Kongono LSP RPC error'));
      } else {
        resolve(msg.result);
      }
      return;
    }
    if (msg.method === 'textDocument/publishDiagnostics' && msg.params && msg.params.uri) {
      const uri = vscode.Uri.parse(msg.params.uri);
      const list = (msg.params.diagnostics || []).map((d) => {
        const range = new vscode.Range(
          d.range.start.line,
          d.range.start.character,
          d.range.end.line,
          d.range.end.character,
        );
        const sev = d.severity === 1 ? vscode.DiagnosticSeverity.Error : vscode.DiagnosticSeverity.Warning;
        const diag = new vscode.Diagnostic(range, d.message, sev);
        diag.code = d.code;
        diag.source = d.source || 'Kongono';
        return diag;
      });
      this.diagnostics.set(uri, list);
    }
  }
}

function isSupportedDoc(doc) {
  return doc.uri.scheme === 'file' && (doc.fileName.endsWith('.kgn.html') || doc.fileName.endsWith('.php'));
}

function runTerminalCommand(workspaceRoot, argsList) {
  if (!vscode.workspace.isTrusted) {
    vscode.window.showWarningMessage('Kongono commands require a trusted workspace.');
    return;
  }
  const cfg = vscode.workspace.getConfiguration('kongono');
  const phpBin = cfg.get('phpPath', 'php') || 'php';
  const appPath = cfg.get('appPath', '') || '';
  const kongoBin = fs.existsSync(path.join(workspaceRoot, 'vendor', 'bin', 'kongo'))
    ? path.join(workspaceRoot, 'vendor', 'bin', 'kongo')
    : path.join(workspaceRoot, 'bin', 'kongo');
  const fullArgs = [kongoBin, ...argsList];
  if (appPath) {
    fullArgs.push('--app=' + appPath);
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

  const folders = vscode.workspace.workspaceFolders;
  const root = folders && folders.length ? folders[0].uri.fsPath : null;
  if (root && vscode.workspace.isTrusted) {
    client.start(root);
  }

  const selector = [
    { language: 'kongono-template', scheme: 'file' },
    { language: 'php', scheme: 'file' },
  ];

  context.subscriptions.push(
    output,
    diagnostics,
    vscode.workspace.onDidGrantWorkspaceTrust(() => { if (root) client.start(root); }),
    vscode.workspace.onDidOpenTextDocument((doc) => { if (isSupportedDoc(doc)) client.didOpen(doc); }),
    vscode.workspace.onDidChangeTextDocument((ev) => { if (isSupportedDoc(ev.document)) client.didChange(ev.document); }),
    vscode.workspace.onDidSaveTextDocument((doc) => { if (isSupportedDoc(doc)) client.didSave(doc); }),
    vscode.workspace.onDidCloseTextDocument((doc) => { if (isSupportedDoc(doc)) client.didClose(doc); }),
    vscode.languages.registerHoverProvider(selector, {
      async provideHover(doc, pos) {
        if (!client.ready) return null;
        const res = await client.request('textDocument/hover', {
          textDocument: { uri: doc.uri.toString() },
          position: { line: pos.line, character: pos.character },
        });
        if (!res || !res.contents || !res.contents.value) return null;
        return new vscode.Hover(new vscode.MarkdownString(res.contents.value));
      },
    }),
    vscode.languages.registerCodeActionsProvider(selector, {
      async provideCodeActions(doc, range) {
        if (!client.ready) return [];
        const res = await client.request('textDocument/codeAction', {
          textDocument: { uri: doc.uri.toString() },
          range: {
            start: { line: range.start.line, character: range.start.character },
            end: { line: range.end.line, character: range.end.character },
          },
          context: { diagnostics: [] },
        });
        if (!Array.isArray(res)) return [];
        return res.map((a) => {
          const action = new vscode.CodeAction(a.title, vscode.CodeActionKind.QuickFix);
          action.isPreferred = Boolean(a.isPreferred);
          const edit = new vscode.WorkspaceEdit();
          const changes = (a.edit && a.edit.changes) || {};
          for (const [uriStr, edits] of Object.entries(changes)) {
            const targetUri = vscode.Uri.parse(uriStr);
            for (const e of edits) {
              edit.replace(
                targetUri,
                new vscode.Range(e.range.start.line, e.range.start.character, e.range.end.line, e.range.end.character),
                e.newText,
              );
            }
          }
          action.edit = edit;
          return action;
        });
      },
    }, { providedCodeActionKinds: [vscode.CodeActionKind.QuickFix] }),
    vscode.commands.registerCommand('kongono.prove', () => { if (root) runTerminalCommand(root, ['prove']); }),
    vscode.commands.registerCommand('kongono.diagnose', () => { if (root) runTerminalCommand(root, ['diagnose']); }),
    vscode.commands.registerCommand('kongono.autofix', () => { if (root) runTerminalCommand(root, ['diagnose', '--fix']); }),
    vscode.commands.registerCommand('kongono.restartLsp', () => { if (root) client.start(root); }),
    { dispose: () => client.stop() },
  );
}

function deactivate() {}

module.exports = { activate, deactivate };
