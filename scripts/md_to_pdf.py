#!/usr/bin/env python3
"""Convierte un .md a PDF vía HTML + LibreOffice headless."""

from __future__ import annotations

import argparse
import subprocess
import sys
from pathlib import Path

import markdown


CSS = """
@page { margin: 2cm; }
body {
  font-family: "Liberation Sans", Arial, sans-serif;
  font-size: 11pt;
  line-height: 1.45;
  color: #1a1a1a;
  max-width: 100%;
}
h1 { font-size: 22pt; color: #003366; border-bottom: 2px solid #003366; padding-bottom: 0.3em; }
h2 { font-size: 16pt; color: #003366; margin-top: 1.4em; page-break-after: avoid; }
h3 { font-size: 13pt; color: #004488; margin-top: 1.1em; page-break-after: avoid; }
h4 { font-size: 11.5pt; color: #333; }
p, li { orphans: 3; widows: 3; }
code, pre { font-family: "Liberation Mono", Consolas, monospace; font-size: 9.5pt; }
pre {
  background: #f4f4f4;
  border: 1px solid #ddd;
  padding: 0.6em 0.8em;
  white-space: pre-wrap;
  word-break: break-word;
}
code { background: #f0f0f0; padding: 0.1em 0.25em; }
table { border-collapse: collapse; width: 100%; margin: 1em 0; font-size: 10pt; }
th, td { border: 1px solid #bbb; padding: 0.35em 0.5em; text-align: left; vertical-align: top; }
th { background: #e8eef5; font-weight: bold; }
img { max-width: 100%; height: auto; margin: 0.5em 0; }
hr { border: none; border-top: 1px solid #ccc; margin: 1.5em 0; }
blockquote { border-left: 3px solid #003366; margin-left: 0; padding-left: 1em; color: #444; }
ul, ol { padding-left: 1.4em; }
a { color: #004488; text-decoration: none; }
"""


def md_to_html(md_path: Path, html_path: Path) -> None:
    text = md_path.read_text(encoding="utf-8")
    body = markdown.markdown(
        text,
        extensions=["tables", "fenced_code", "sane_lists", "toc"],
    )
    title = md_path.stem.replace("-", " ")
    html = f"""<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{title}</title>
<style>{CSS}</style>
</head>
<body>
{body}
</body>
</html>
"""
    html_path.write_text(html, encoding="utf-8")


def html_to_pdf(html_path: Path, pdf_path: Path, soffice: str) -> None:
    outdir = pdf_path.parent
    subprocess.run(
        [
            soffice,
            "--headless",
            "--nologo",
            "--nofirststartwizard",
            "--convert-to",
            "pdf",
            "--outdir",
            str(outdir),
            str(html_path),
        ],
        check=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )
    generated = outdir / f"{html_path.stem}.pdf"
    if generated != pdf_path and generated.exists():
        generated.replace(pdf_path)


def find_soffice() -> str:
    for candidate in ("/usr/bin/soffice", "soffice"):
        try:
            subprocess.run(
                [candidate, "--version"],
                check=True,
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
            )
            return candidate
        except (FileNotFoundError, subprocess.CalledProcessError):
            continue
    raise SystemExit("ERROR: LibreOffice (soffice) no encontrado.")


def main() -> int:
    parser = argparse.ArgumentParser(description="Convierte Markdown a PDF.")
    parser.add_argument("input", type=Path, help="Archivo .md de entrada")
    parser.add_argument(
        "-o",
        "--output",
        type=Path,
        help="Archivo .pdf de salida (por defecto: mismo nombre que el .md)",
    )
    args = parser.parse_args()

    md_path = args.input.resolve()
    if not md_path.is_file():
        raise SystemExit(f"ERROR: no existe {md_path}")

    pdf_path = (args.output or md_path.with_suffix(".pdf")).resolve()
    html_path = md_path.with_suffix(".html")

    md_to_html(md_path, html_path)
    soffice = find_soffice()
    html_to_pdf(html_path, pdf_path, soffice)

    if html_path.exists():
        html_path.unlink()

    print(f"PDF generado: {pdf_path}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
