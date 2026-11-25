import { defineConfig } from "cf/config";

export default defineConfig({
  worker: {
    name: "kongono",
    compatibilityDate: "2026-11-25",
    domains: ["kongono.com", "www.kongono.com"],
    workersDev: true,
    previewUrls: true,
    assets: {
      htmlHandling: "auto-trailing-slash",
      notFoundHandling: "none",
    },
  },
});
