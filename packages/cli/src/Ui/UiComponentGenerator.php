<?php

declare(strict_types=1);

namespace Kongono\Cli\Ui;

use InvalidArgumentException;

/**
 * Generates copy-owned, accessible, zero-npm, strict-CSP UI components (`kongo ui:add`)
 * built on native browser primitives (`<dialog>`, `popover`, `<details name="...">`) and Kongono Live 2.5.
 */
final readonly class UiComponentGenerator
{
    /** @var list<string> */
    public const array AVAILABLE = ['modal', 'drawer', 'dropdown', 'tabs', 'toast', 'datatable', 'kanban'];

    /**
     * Generate component templates and shared stylesheet for the requested component names (or `['all']`).
     *
     * @param list<string> $names
     * @return array<string, string> Relative file path => file contents
     */
    public function generate(array $names): array
    {
        $selected = [];
        foreach ($names as $raw) {
            $clean = strtolower(trim($raw));
            if ('all' === $clean) {
                $selected = self::AVAILABLE;
                break;
            }
            if ('' === $clean) {
                continue;
            }
            if (!in_array($clean, self::AVAILABLE, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown Kongono UI component "%s". Available: %s, all.',
                    $raw,
                    implode(', ', self::AVAILABLE),
                ));
            }
            $selected[] = $clean;
        }

        $selected = array_values(array_unique($selected));
        if ([] === $selected) {
            throw new InvalidArgumentException(sprintf(
                'Specify at least one component to add. Available: %s, all.',
                implode(', ', self::AVAILABLE),
            ));
        }

        $files = [
            'public/assets/kongono-ui.css' => $this->stylesheet(),
        ];

        foreach ($selected as $component) {
            $files['views/components/k-' . $component . '.kgn.html'] = match ($component) {
                'modal' => $this->modalTemplate(),
                'drawer' => $this->drawerTemplate(),
                'dropdown' => $this->dropdownTemplate(),
                'tabs' => $this->tabsTemplate(),
                'toast' => $this->toastTemplate(),
                'datatable' => $this->datatableTemplate(),
                'kanban' => $this->kanbanTemplate(),
            };
        }

        return $files;
    }

    private function modalTemplate(): string
    {
        return <<<'HTML'
{{-- Accessible Kongono UI Modal (`<x-k-modal id="confirm-modal" title="Confirm Action" trigger="Open Modal">...</x-k-modal>`) --}}
<div
  class="kui-modal-root"
  @state(['open' => false])
  k-on:keydown.escape="set(open, false)"
>
  <button type="button" class="kui-btn" k-on:click="set(open, true)">{{ $trigger ?? 'Open' }}</button>
  <div class="kui-backdrop" k-show="open" k-on:click.self="set(open, false)">
    <div id="{{ $id ?? 'kui-modal' }}" class="kui-modal" role="dialog" aria-modal="true" aria-label="{{ $title ?? 'Dialog' }}">
      <div class="kui-modal-head">
        <h3 class="kui-modal-title">{{ $title ?? 'Dialog' }}</h3>
        <button type="button" class="kui-btn kui-btn-sm" k-on:click="set(open, false)" aria-label="Close">Close</button>
      </div>
      <div class="kui-modal-body">
        {!! $slot !!}
      </div>
    </div>
  </div>
</div>
HTML;
    }

    private function drawerTemplate(): string
    {
        return <<<'HTML'
{{-- Accessible Slide-Over Drawer (`<x-k-drawer id="filters-drawer" title="Filters" trigger="Open Drawer">...</x-k-drawer>`) --}}
<div
  class="kui-drawer-root"
  @state(['open' => false])
  k-on:keydown.escape="set(open, false)"
>
  <button type="button" class="kui-btn" k-on:click="set(open, true)">{{ $trigger ?? 'Open Drawer' }}</button>
  <div class="kui-backdrop kui-backdrop-end" k-show="open" k-on:click.self="set(open, false)">
    <aside id="{{ $id ?? 'kui-drawer' }}" class="kui-drawer" aria-label="{{ $title ?? 'Drawer' }}">
      <div class="kui-modal-head">
        <h3 class="kui-modal-title">{{ $title ?? 'Panel' }}</h3>
        <button type="button" class="kui-btn kui-btn-sm" k-on:click="set(open, false)" aria-label="Close">Close</button>
      </div>
      <div class="kui-modal-body">
        {!! $slot !!}
      </div>
    </aside>
  </div>
</div>
HTML;
    }

    private function dropdownTemplate(): string
    {
        return <<<'HTML'
{{-- Native Popover API Zero-JS Dropdown (`<x-k-dropdown id="actions-menu" label="Actions">...</x-k-dropdown>`) --}}
<div class="kui-dropdown">
  <button type="button" class="kui-btn" popovertarget="{{ $id ?? 'kui-popover' }}" aria-haspopup="menu">
    {{ $label ?? 'Menu' }} ▾
  </button>
  <div id="{{ $id ?? 'kui-popover' }}" class="kui-popover" popover="auto" role="menu">
    {!! $slot !!}
  </div>
</div>
HTML;
    }

    private function tabsTemplate(): string
    {
        return <<<'HTML'
{{-- Zero-eval Client Tabs (`<x-k-tabs :tabs="['overview' => 'Overview', 'settings' => 'Settings']" initial="overview">...</x-k-tabs>`) --}}
<div
  class="kui-tabs"
  @state(['activeTab' => $initial ?? 'overview'])
  k-persist="session"
>
  <div class="kui-tab-list" role="tablist">
    @foreach ($tabs ?? [] as $key => $label)
      <button
        type="button"
        role="tab"
        class="kui-tab"
        k-class:is-active="activeTab === '{{ preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $key) }}'"
        k-attr:aria-selected="activeTab === '{{ preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $key) }}'"
        k-on:click="set(activeTab, '{{ preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $key) }}')"
      >{{ $label }}</button>
    @endforeach
  </div>
  <div class="kui-tab-panels">
    {!! $slot !!}
  </div>
</div>
HTML;
    }

    private function toastTemplate(): string
    {
        return <<<'HTML'
{{-- Out-of-Band Live Toast Region (`<x-k-toast id="toast-region" />` updated via `kgn-oob="beforeend"`) --}}
<div id="{{ $id ?? 'toast-region' }}" class="kui-toast-stack" role="status" aria-live="polite">
  {!! $slot !!}
</div>
HTML;
    }

    private function datatableTemplate(): string
    {
        return <<<'HTML'
{{-- Live 2.5 Searchable Data Table (`<x-k-datatable id="users-table" endpoint="/live/users" :columns="['Name', 'Role']">...</x-k-datatable>`) --}}
<section id="{{ $id ?? 'kui-table' }}" class="kui-table-card" @state(['q' => ''])>
  <div class="kui-table-toolbar">
    <input
      type="search"
      name="q"
      class="kui-input"
      placeholder="{{ $placeholder ?? 'Search records...' }}"
      @if (isset($endpoint) && '' !== $endpoint)
        kgn-get="{{ $endpoint }}"
        kgn-trigger="input delay:200ms"
        kgn-target="#{{ $id ?? 'kui-table' }}"
        kgn-swap="morph"
      @else
        k-model="q"
      @endif
    >
  </div>
  <div class="kui-table-scroll">
    <table class="kui-table">
      <thead>
        <tr>
          @foreach ($columns ?? [] as $col)
            <th>{{ $col }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        {!! $slot !!}
      </tbody>
    </table>
  </div>
</section>
HTML;
    }

    private function kanbanTemplate(): string
    {
        return <<<'HTML'
{{-- Zero-React Drag-and-Drop Sortable List/Column (`<x-k-kanban id="col-todo" title="To Do" reorder-url="/live/reorder">...</x-k-kanban>`) --}}
<section class="kui-kanban-col">
  <header class="kui-kanban-head">
    <h4>{{ $title ?? 'Column' }}</h4>
  </header>
  <div
    id="{{ $id ?? 'kui-kanban-list' }}"
    class="kui-kanban-list"
    kgn-sortable="{{ $reorderUrl ?? '/live/reorder' }}"
    kgn-swap="morph"
  >
    {!! $slot !!}
  </div>
</section>
HTML;
    }

    private function stylesheet(): string
    {
        return <<<'CSS'
/* Kongono UI Primitives — Zero-npm, Strict-CSP Accessible Styles */
.kui-btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
  font: 600 0.85rem/1.2 system-ui, -apple-system, sans-serif;
  padding: 0.5rem 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1;
  background: #ffffff; color: #0f172a; cursor: pointer;
}
.kui-btn:hover { background: #f8fafc; border-color: #94a3b8; }
.kui-btn-sm { font-size: 0.78rem; padding: 0.35rem 0.65rem; }
.kui-input {
  width: 100%; font: 400 0.88rem/1.4 system-ui, -apple-system, sans-serif;
  padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #0f172a;
}
.kui-backdrop {
  position: fixed; inset: 0; z-index: 80; background: rgba(15, 23, 42, 0.48);
  backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1rem;
}
.kui-backdrop-end { justify-content: flex-end; align-items: stretch; padding: 0; }
.kui-modal {
  width: min(540px, 100%); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px;
  padding: 1.25rem 1.4rem; box-shadow: 0 24px 64px rgba(15, 23, 42, 0.22);
}
.kui-drawer {
  width: min(420px, 100%); height: 100%; background: #ffffff; border-left: 1px solid #e2e8f0;
  padding: 1.25rem 1.4rem; overflow-y: auto; box-shadow: -12px 0 36px rgba(15, 23, 42, 0.16);
}
.kui-modal-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.9rem; }
.kui-modal-title { margin: 0; font-size: 1.08rem; }
.kui-popover {
  border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.45rem; background: #ffffff;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14); min-width: 180px;
}
.kui-tab-list { display: inline-flex; gap: 0.25rem; padding: 0.25rem; background: #f1f5f9; border-radius: 10px; margin-bottom: 0.85rem; }
.kui-tab { border: none; background: transparent; padding: 0.4rem 0.8rem; border-radius: 8px; font-weight: 600; color: #475569; cursor: pointer; }
.kui-tab.is-active { background: #ffffff; color: #0f172a; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08); }
.kui-toast-stack { position: fixed; right: 1rem; bottom: 1rem; z-index: 90; display: flex; flex-direction: column; gap: 0.5rem; max-width: 360px; }
.kui-table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.kui-table-toolbar { padding: 0.75rem; border-bottom: 1px solid #e2e8f0; }
.kui-table-scroll { overflow-x: auto; }
.kui-table { width: 100%; border-collapse: collapse; font-size: 0.87rem; }
.kui-table th, .kui-table td { text-align: left; padding: 0.65rem 0.85rem; border-bottom: 1px solid #f1f5f9; }
.kui-kanban-col { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.85rem; }
.kui-kanban-head h4 { margin: 0 0 0.65rem; font-size: 0.92rem; }
.kui-kanban-list { display: flex; flex-direction: column; gap: 0.5rem; min-height: 48px; }
[data-sortable-item] { cursor: grab; }
[data-sortable-item].is-dragging { opacity: 0.45; cursor: grabbing; }
CSS;
    }
}
