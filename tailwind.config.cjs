/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./app/views/**/*.php",
    "./resources/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        app: {
          bg: "#f8fafc",
          panel: "#ffffff",
          panelSoft: "#fbfcfd",
          panelSubtle: "#f8fafb",
          panelHover: "#eef3f6",
          topbar: "#ffffff",
          sidebar: "#ffffff",
          navHover: "#e9eef2",
          border: "#dde1e6",
          borderSoft: "#d5dbe3",
          borderControl: "#cfd6df",
          borderControlStrong: "#cdd5df",
          borderSuccess: "#d4ead7",
          borderWarning: "#fcd34d",
          text: "#1f2937",
          muted: "#6b7280",
          mutedSoft: "#9aa2af",
          textSubtle: "#374151",
          textOnBrand: "#ffffff",
          accent: "#0A8B81",
          accentHover: "#0e8f82",
          accentStrong: "#0d9688",
          brand: "#0d9688",
          accentSoft: "#e8fdfb",
          successBg: "#e9f8ef",
          successText: "#237a43",
          warningBg: "#fffbeb",
          warningText: "#92400e",
          link: "#0f766e",
        },
      },
      boxShadow: {
        xsSoft: "0 1px 2px rgba(16, 24, 40, 0.06)",
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}