<?php

declare(strict_types=1);

namespace Kongono\Cli\Bridge;

use InvalidArgumentException;

/**
 * Scaffolds first-class React 19, Vue 3, or Svelte 5 SPA Bridge adapters wired cleanly
 * to Kongono's `BridgeMiddleware`, `Response::page()`, `ViteManifest`, and typed PHP DTOs.
 */
final readonly class BridgeScaffolder
{
    /** @var list<string> */
    public const array SUPPORTED_FRAMEWORKS = ['react', 'vue', 'svelte'];

    /**
     * @return array<string, string> Map of relative file path => contents
     */
    public function generate(string $framework): array
    {
        $clean = strtolower(trim($framework));
        if (!in_array($clean, self::SUPPORTED_FRAMEWORKS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported Bridge framework "%s". Supported: %s.',
                $framework,
                implode(', ', self::SUPPORTED_FRAMEWORKS),
            ));
        }

        return match ($clean) {
            'react' => $this->reactFiles(),
            'vue' => $this->vueFiles(),
            'svelte' => $this->svelteFiles(),
        };
    }

    /** @return array<string, string> */
    private function reactFiles(): array
    {
        return [
            'vite.config.ts' => <<<'TS'
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  build: {
    manifest: true,
    outDir: 'public/build',
    rollupOptions: {
      input: 'resources/js/app.tsx',
    },
  },
});
TS,
            'resources/js/kongono-bridge.d.ts' => $this->bridgeAmbientTypes(),
            'resources/js/app.tsx' => <<<'TSX'
import React, { useState } from 'react';
import { createRoot, type Root } from 'react-dom/client';

const pages = import.meta.glob('./pages/**/*.tsx');
let root: Root | null = null;

export function useKongonoForm<T extends Record<string, unknown>>(initial: T) {
  const [data, setData] = useState<T>(initial);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [processing, setProcessing] = useState(false);

  async function submit(method: 'POST' | 'PUT' | 'PATCH' | 'DELETE', url: string) {
    setProcessing(true);
    setErrors({});
    try {
      return await window.KongonoBridge.visit(url, {
        method,
        data,
        onError: (errs) => setErrors(errs),
      });
    } finally {
      setProcessing(false);
    }
  }

  return { data, setData, errors, processing, submit };
}

window.KongonoBridge.boot({
  el: '#app',
  resolve: async (name: string) => {
    const loader = pages[`./pages/${name}.tsx`];
    if (!loader) throw new Error(`Kongono Bridge React page not found: ${name}`);
    return (await loader()) as { default: React.ComponentType<Record<string, unknown>> };
  },
  mount: ({ el, Component, props }) => {
    root ??= createRoot(el);
    root.render(React.createElement(Component as React.ComponentType<Record<string, unknown>>, props));
  },
});
TSX,
            'resources/js/pages/Home.tsx' => <<<'TSX'
import React from 'react';

export interface HomeProps {
  title: string;
  phpVersion?: string;
}

export default function Home({ title, phpVersion }: HomeProps) {
  return (
    <main style={{ maxWidth: 760, margin: '3rem auto', fontFamily: 'system-ui, sans-serif' }}>
      <h1>{title}</h1>
      <p>Powered by Kongono Bridge + React 19 on PHP {phpVersion ?? '8.5'}.</p>
      <a href="/about" k-link="">Navigate without full page reload &rarr;</a>
    </main>
  );
}
TSX,
        ];
    }

    /** @return array<string, string> */
    private function vueFiles(): array
    {
        return [
            'vite.config.ts' => <<<'TS'
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  build: {
    manifest: true,
    outDir: 'public/build',
    rollupOptions: {
      input: 'resources/js/app.ts',
    },
  },
});
TS,
            'resources/js/kongono-bridge.d.ts' => $this->bridgeAmbientTypes(),
            'resources/js/app.ts' => <<<'TS'
import { createApp, h, reactive, shallowRef, type Component } from 'vue';

const pages = import.meta.glob('./pages/**/*.vue');
const currentComponent = shallowRef<Component | null>(null);
const currentProps = reactive<Record<string, unknown>>({});
let mounted = false;

window.KongonoBridge.boot({
  el: '#app',
  resolve: async (name: string) => {
    const loader = pages[`./pages/${name}.vue`];
    if (!loader) throw new Error(`Kongono Bridge Vue page not found: ${name}`);
    return (await loader()) as { default: Component };
  },
  mount: ({ el, Component: PageComp, props }) => {
    currentComponent.value = PageComp as Component;
    Object.keys(currentProps).forEach((k) => delete currentProps[k]);
    Object.assign(currentProps, props);
    if (!mounted) {
      createApp({
        render: () => (currentComponent.value ? h(currentComponent.value, currentProps) : null),
      }).mount(el);
      mounted = true;
    }
  },
});
TS,
            'resources/js/pages/Home.vue' => <<<'VUE'
<script setup lang="ts">
defineProps<{ title: string; phpVersion?: string }>();
</script>

<template>
  <main style="max-width: 760px; margin: 3rem auto; font-family: system-ui, sans-serif">
    <h1>{{ title }}</h1>
    <p>Powered by Kongono Bridge + Vue 3 on PHP {{ phpVersion ?? '8.5' }}.</p>
    <a href="/about" k-link>Navigate without full page reload &rarr;</a>
  </main>
</template>
VUE,
        ];
    }

    /** @return array<string, string> */
    private function svelteFiles(): array
    {
        return [
            'vite.config.ts' => <<<'TS'
import { defineConfig } from 'vite';
import { svelte } from '@sveltejs/vite-plugin-svelte';

export default defineConfig({
  plugins: [svelte()],
  build: {
    manifest: true,
    outDir: 'public/build',
    rollupOptions: {
      input: 'resources/js/app.ts',
    },
  },
});
TS,
            'resources/js/kongono-bridge.d.ts' => $this->bridgeAmbientTypes(),
            'resources/js/app.ts' => <<<'TS'
import { mount, unmount } from 'svelte';

const pages = import.meta.glob('./pages/**/*.svelte');
let activeInstance: Record<string, unknown> | null = null;

window.KongonoBridge.boot({
  el: '#app',
  resolve: async (name: string) => {
    const loader = pages[`./pages/${name}.svelte`];
    if (!loader) throw new Error(`Kongono Bridge Svelte page not found: ${name}`);
    return (await loader()) as { default: unknown };
  },
  mount: ({ el, Component, props }) => {
    if (activeInstance) unmount(activeInstance);
    activeInstance = mount(Component as never, { target: el, props });
  },
});
TS,
            'resources/js/pages/Home.svelte' => <<<'SVELTE'
<script lang="ts">
  let { title, phpVersion = '8.5' }: { title: string; phpVersion?: string } = $props();
</script>

<main style="max-width: 760px; margin: 3rem auto; font-family: system-ui, sans-serif">
  <h1>{title}</h1>
  <p>Powered by Kongono Bridge + Svelte 5 on PHP {phpVersion}.</p>
  <a href="/about" k-link>Navigate without full page reload &rarr;</a>
</main>
SVELTE,
        ];
    }

    private function bridgeAmbientTypes(): string
    {
        return <<<'DTS'
export interface KongonoBridgePage<P = Record<string, unknown>> {
  component: string;
  props: P;
  url: string;
  version: string;
  errors: Record<string, string[]>;
  csrfToken: string | null;
}

export interface KongonoBridgeVisitOptions {
  method?: string;
  data?: Record<string, unknown> | FormData;
  only?: string[];
  preserveScroll?: boolean;
  replace?: boolean;
  onSuccess?: (page: KongonoBridgePage) => void;
  onError?: (errors: Record<string, string[]>) => void;
}

export interface KongonoBridgeRuntime {
  version: string;
  boot(config: {
    el?: string | Element;
    resolve: (component: string) => Promise<{ default: unknown }>;
    mount: (ctx: { el: Element; Component: unknown; props: Record<string, unknown>; page: KongonoBridgePage }) => void;
  }): Promise<KongonoBridgePage> | null;
  visit(url: string, options?: KongonoBridgeVisitOptions): Promise<{ ok: boolean; status: number; page?: KongonoBridgePage; errors?: Record<string, string[]> } | null>;
  reload(options?: KongonoBridgeVisitOptions): Promise<unknown>;
  page(): KongonoBridgePage | null;
}

declare global {
  interface Window {
    KongonoBridge: KongonoBridgeRuntime;
  }
}
DTS;
    }
}
