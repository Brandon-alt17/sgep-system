# 🎓 SGEP — Sistema de Gestión de Etapa Productiva

**SENA — Centro de Diseño e Innovación Tecnológica Industrial (CDITI)**  
**Versión:** 1.0 (MVP)  
**Estado:** En Desarrollo  
**Año:** 2026

---

## 📖 Descripción

El **SGEP** es un sistema web local diseñado para automatizar el seguimiento, evaluación y documentación de aprendices en etapa productiva del SENA CDITI.

### ❌ Problema que Resuelve

| Antes (Proceso Manual) | Después (Con SGEP) |
|----------------------|-------------------|
| Formularios externos dispersos (Google Forms) | Importación centralizada de CSV/Excel |
| Consolidación manual en Excel (~30 min/aprendiz) | Dashboard automatizado (~5 min/aprendiz) |
| Formato GFPI-F-023 diligenciado a mano | Generación automática con fidelidad oficial |
| Sin alertas de próximas visitas | Agenda integrada con recordatorios (En vista dashboard) |
| Información fragmentada por instructor | Reporte maestro consolidado (58 columnas) |

---

## 🛠️ Tecnologías

| Capa | Tecnología | Versión |
|------|-----------|---------|
| **Backend** | PHP + Laravel | 8.2 + 10.x |
| **Frontend** | Blade + Tailwind CSS | Nativo + 3.x |
| **Base de Datos** | MySQL | 8.0.x |
| **Entorno Local** | WAMP (Windows) / XAMPP (macOS) | Latest |
| **Documentos** | PHPWord | 1.1+ |
| **Excel/CSV** | PhpSpreadsheet + Maatwebsite/Excel | 2.0 + 3.1 |

---

## 📋 Requisitos del Sistema

| Componente | Mínimo | Recomendado |
|-----------|--------|-------------|
| **Sistema Operativo** | Windows 10 / macOS Monterey | Windows 11 / macOS Ventura+ |
| **Procesador** | Intel Core i3 (8ª gen+) / M1 | Intel Core i5 (10ª gen+) / M2 |
| **Memoria RAM** | 8 GB | 16 GB |
| **Almacenamiento** | 256 GB SSD | 512 GB NVMe SSD |
| **Navegador** | Chrome 100+ / Firefox 90+ | Chrome 120+ |

---

## 🚀 Instalación Rápida

### Prerrequisitos

- [ ] WAMP instalado (Windows) o XAMPP (macOS)
- [ ] PHP 8.2+ habilitado
- [ ] Composer instalado globalmente
- [ ] MySQL 8.0+ corriendo

### Pasos de Instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/Brandon-alt17/sgep-system.git
cd sgep-system

# 2. Copiar archivo de entorno
cp .env.example .env

# 3. Instalar dependencias
composer install

# 4. Generar clave de aplicación
php artisan key:generate

# 5. Configurar base de datos en .env
# DB_DATABASE=sgep
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Ejecutar migraciones
php artisan migrate --seed

# 7. Limpiar caché para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Acceder al sistema
# Windows: http://localhost/sgep/public
# macOS: http://localhost/sgep/public