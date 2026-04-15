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
          bg: "#f3f5f7",
          panel: "#ffffff",
          border: "#dde1e6",
          text: "#1f2937",
          muted: "#6b7280",
          accent: "#0f9f8f",
          accentSoft: "#dff4f1",
          successBg: "#e9f8ef",
          successText: "#237a43",
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