import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Workroom — Work queue sample",
  description: "A responsive React and TypeScript work queue with fictional data.",
  icons: {
    icon: "/favicon.svg",
    shortcut: "/favicon.svg",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en">
      <body className="antialiased">{children}</body>
    </html>
  );
}
