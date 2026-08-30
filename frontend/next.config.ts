import type { NextConfig } from "next";

const apiBaseUrl = process.env.NEXT_PUBLIC_API_BASE_URL;
const additionalPatterns: { protocol: "http" | "https"; hostname: string; port?: string }[] = [];

if (apiBaseUrl) {
  try {
    const parsed = new URL(apiBaseUrl);
    if (parsed.protocol === "http:" || parsed.protocol === "https:") {
      additionalPatterns.push({
        protocol: parsed.protocol.replace(":", "") as "http" | "https",
        hostname: parsed.hostname,
        port: parsed.port || undefined,
      });
    }
  } catch {
    // Ignore invalid URL during config evaluation
  }
}

const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "images.unsplash.com",
      },
      {
        protocol: "http",
        hostname: "localhost",
        port: "8000",
      },
      {
        protocol: "http",
        hostname: "127.0.0.1",
        port: "8000",
      },
      ...additionalPatterns,
    ],
  },
};

export default nextConfig;
