import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";

function resolveAllowedHosts(
  value: string | undefined,
): true | string[] | undefined {
  if (!value) {
    return undefined;
  }

  if (value === "*" || value === "true") {
    return true;
  }

  return value
    .split(",")
    .map((host) => host.trim())
    .filter(Boolean);
}

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), "");
  const devProxyTarget = env.VITE_DEV_PROXY_TARGET;

  return {
    plugins: [vue()],
    server: {
      allowedHosts: resolveAllowedHosts(env.VITE_ALLOWED_HOSTS),
      host: "0.0.0.0",
      port: Number(env.VITE_PORT ?? 25300),
      proxy: {
        "/api": {
          target: devProxyTarget,
          changeOrigin: true,
        },
        "/sanctum": {
          target: devProxyTarget,
          changeOrigin: true,
        },
        "/up": {
          target: devProxyTarget,
          changeOrigin: true,
        },
      },
    },
  };
});
