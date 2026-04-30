# Manual de Recuperación y Documentación Técnica - AMFURI PERU S.A.C.

## 1. Estructura del Sistema
El sistema de Asistencia Facial está construido sobre:
- **Lenguaje:** PHP 8.2+
- **Base de Datos:** MariaDB / MySQL
- **IA Facial:** face-api.js (Local)
- **Frontend:** Bootstrap 5.3 + JavaScript

## 2. Procedimiento de Recuperación (Backup)
Si el sistema falla o se traslada de servidor:
1. **Base de Datos:** Importar el archivo `database/control_asistencia.sql` en phpMyAdmin.
2. **Archivos:** Copiar toda la carpeta `asistencia_facial` a `htdocs`.
3. **Fotos:** Asegurarse de que la carpeta `uploads/fotos/` tenga permisos de escritura (0777).

## 3. Seguridad Crítica
- **Bloqueo de IPs:** Si un administrador se bloquea accidentalmente, debe limpiar la tabla `login_attempts` en la base de datos:
  ```sql
  DELETE FROM login_attempts;
  ```
- **Tokens CSRF:** Todos los formularios deben incluir el campo oculto `csrf_token` generado por la función `generarTokenCSRF()`.
- **Cifrado:** Los números de cuenta en la base de datos están cifrados con AES-256-CBC usando la clave definida en `config/security.php`.

## 4. Solución de Problemas Comunes
- **La cámara no carga:** Verificar que el sitio use HTTPS o que esté en `localhost`. Los navegadores bloquean la cámara en sitios HTTP no seguros.
- **Error 403:** Verificar que la sesión no haya expirado o que el usuario tenga perfil de 'Administrador'.
- **Error de Conexión DB:** Revisar las credenciales en `config/db.php`.

---
*Documentación generada el 29-04-2026*
