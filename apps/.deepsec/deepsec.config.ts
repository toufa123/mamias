import { defineConfig } from "deepsec/config";

export default defineConfig({
  projects: [
    { id: "apps", root: ".." },
    // <deepsec:projects-insert-above>
  ],
});
