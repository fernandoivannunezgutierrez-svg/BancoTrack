# BancoTrack

Sistema web en **PHP + MySQL** para el seguimiento de cartera de clientes, registro de gestiones comerciales e importación masiva desde archivos CSV en entorno **XAMPP**.

---

## Estructura del proyecto

```text
bancotrack/
├── data/
│   └── clientes_prueba.csv          # 10 clientes de ejemplo en Chile (separados por ;)
├── database/
│   └── 01_schema_xampp_mysql.sql    # Esquema MySQL/MariaDB y datos semilla
├── index.php                        # Punto de entrada principal (http://localhost/bancotrack/)
├── .gitignore
└── README.md
```

---

## Requisitos y puesta en marcha en XAMPP

1. **Iniciar servicios en XAMPP Control Panel:**
   - Inicia **Apache**.
   - Inicia **MySQL**.
2. **Importar la base de datos en phpMyAdmin:**
   - Abre [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
   - Ve a la pestaña **Importar** y selecciona `database/01_schema_xampp_mysql.sql` (o ejecuta su contenido en la pestaña **SQL**).
   - Se creará la base de datos `bancotrack` con las tablas `roles`, `usuarios`, `clientes`, `gestiones` e `importaciones_csv`.
3. **Abrir la aplicación:**
   - Ingresa a [http://localhost/bancotrack/](http://localhost/bancotrack/).
4. **Probar el importador CSV:**
   - Utiliza `data/clientes_prueba.csv` (codificación UTF-8, delimitador `;`, 10 registros de ejemplo en Chile).

---

## Roles demo incluidos

El esquema SQL incluye tres usuarios de demostración:

| Rol | Correo | Descripción |
| --- | --- | --- |
| `admin` | `admin@bancotrack.cl` | Acceso total a configuración, usuarios y auditoría |
| `ejecutivo` | `ejecutivo@bancotrack.cl` | Gestión de cartera de clientes, seguimiento e importación CSV |
| `auditor` | `auditor@bancotrack.cl` | Consulta de solo lectura sobre reportes y trazabilidad |

---

## Notas de desarrollo

Este proyecto fue desarrollado con asistencia de IA (**Google Antigravity / Gemini**), manteniendo control humano directo sobre la arquitectura del sistema, el diseño de la base de datos y las sentencias SQL.
