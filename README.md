# BancoTrack — Portal Bancario, Migración ETL y Control RBAC

Sistema web enfocado en el sector bancario y financiero para gestionar la **trazabilidad de solicitudes y compras**, la **carga masiva de clientes desde Excel/CSV** y el **control de acceso basado en roles (RBAC)**. Diseñado para ejecutarse en minutos en entorno local (**XAMPP: Apache + PHP + MariaDB/MySQL**) para que cualquier evaluador o reclutador pueda probar sus flujos de negocio en vivo.

---

## Valor Empresarial del Proyecto

- **Seguridad y Segregación de Funciones (RBAC):** Vistas y permisos diferenciados para `CLIENTE`, `EJECUTIVO` y `ANALISTA_DATOS`, con validación de rol directamente en base de datos.
- **Integridad Transaccional:** El avance de estados (`RECIBIDA` → `EN_EVALUACION` → `APROBADA` / `RECHAZADA` → `FINALIZADA`) se ejecuta mediante el procedimiento almacenado `sp_actualizar_estado_solicitud` con bloqueo de fila (`FOR UPDATE`) y registro automático en `historial_estados`.
- **Migración Masiva (ETL) Auditada:** Carga de archivos CSV exportados desde Excel con inserción/actualización segura (`ON DUPLICATE KEY UPDATE`) y bitácora de control en `log_migracion_excel`.
- **Experiencia de Usuario Clara:** Barra de progreso visual y selector rápido de perfiles demo para validar cada flujo sin configuraciones complejas.

---

## Stack Tecnológico

| Capa | Tecnología |
| :--- | :--- |
| **Servidor Local** | Apache (XAMPP) |
| **Backend** | PHP 8+ (PDO con consultas preparadas) |
| **Base de Datos** | MariaDB / MySQL (`InnoDB`, Stored Procedures, Claves Foráneas) |
| **Frontend** | HTML5, CSS3 |
| **Control y Documentación** | Git, GitHub, desarrollo asistido con IA (Google Antigravity / Gemini) manteniendo control de arquitectura, modelamiento de datos y validación SQL |

---

## Instalación y Ejecución en Localhost (XAMPP)

1. **Clonar o copiar el proyecto** dentro de la carpeta pública de XAMPP:
   ```text
   C:\xampp\htdocs\bancotrack\
   ```
2. **Iniciar Apache y MySQL** desde el Panel de Control de XAMPP.
3. **Importar la base de datos**:
   - Abre `http://localhost/phpmyadmin`.
   - Ve a la pestaña **Importar**, selecciona `database/01_schema_xampp_mysql.sql` y ejecuta. Esto creará la base `bancotrack`, las tablas, el procedimiento almacenado y los datos de prueba.
4. **Abrir la aplicación**:
   - Ingresa a `http://localhost/bancotrack/` en tu navegador.

---

## Roles de Demostración Incluidos

Desde la barra superior de la aplicación puedes alternar en un clic entre los perfiles de prueba:

- **Cliente:** Visualiza únicamente sus propias compras/solicitudes y su barra de progreso en tiempo real.
- **Ejecutivo:** Accede a la bandeja global de solicitudes y actualiza estados ejecutando el *Stored Procedure* con comentario de respaldo.
- **Analista de Datos (ETL):** Sube archivos `.csv` masivos (formato `RUT;Nombre;Telefono;Ciudad;Segmento;Cupo`) y revisa el historial de cargas auditadas.

---

## Autor

**Fernando Iván Núñez Gutiérrez** — Estudiante de Ingeniería Civil en Informática (UCSC)
- **LinkedIn:** [Fernando Núñez Gutiérrez](https://www.google.com/search?q=https://www.linkedin.com/in/fernando-n%25C3%25BA%25C3%25B1ez-guti%25C3%25A9rrez-5b3175318)
- **GitHub:** [@fernandoivannunezgutierrez-svg](https://github.com/fernandoivannunezgutierrez-svg)
- **Correo:** `fernando.ivan.nunez.gutierrez@gmail.com`
