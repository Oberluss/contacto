# 📧 Sistema de Contacto PHP

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.0+-blue.svg" alt="PHP Version">
  <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="License">
  <img src="https://img.shields.io/badge/Version-1.0.0-orange.svg" alt="Version">
  <img src="https://img.shields.io/badge/Status-Active-success.svg" alt="Status">
</p>

<p align="center">
  <strong>Sistema de contacto profesional, seguro y fácil de instalar para cualquier sitio web</strong>
</p>

<p align="center">
  <a href="#-características">Características</a> •
  <a href="#-demo">Demo</a> •
  <a href="#-instalación">Instalación</a> •
  <a href="#-uso">Uso</a> •
  <a href="#-configuración">Configuración</a> •
  <a href="#-personalización">Personalización</a>
</p>

---

## 📋 Descripción

Sistema de contacto completo en PHP que permite a los visitantes de tu sitio web enviar mensajes de forma segura y eficiente. Incluye un panel de administración para gestionar todos los mensajes recibidos, sin necesidad de base de datos.

### ¿Por qué usar este sistema?

- **Sin base de datos**: Usa archivos de texto plano
- **Instalación en 1 minuto**: Con nuestro instalador automático
- **100% personalizable**: Adapta los estilos a tu marca
- **Panel de administración**: Gestiona todos tus mensajes
- **Seguro**: Validación de datos y protección contra spam

## ✨ Características

### 📬 Formulario de Contacto
- ✅ Diseño moderno y responsivo
- ✅ Validación en tiempo real
- ✅ Mensajes de éxito/error animados
- ✅ Compatible con todos los navegadores modernos
- ✅ Accesible y optimizado para SEO

### 🔐 Panel de Administración
- ✅ Acceso protegido por contraseña
- ✅ Vista de todos los mensajes
- ✅ Búsqueda y filtrado
- ✅ Estadísticas de mensajes
- ✅ Eliminación individual de mensajes
- ✅ Información detallada (IP, fecha, navegador)

### 🛡️ Seguridad
- ✅ Sanitización de datos
- ✅ Protección contra inyección SQL
- ✅ Rate limiting por IP
- ✅ Archivos protegidos con .htaccess
- ✅ Validación de email

### 🔧 Características Técnicas
- ✅ PHP 7.0+ compatible
- ✅ Sin dependencias externas
- ✅ Instalador automático
- ✅ Configuración flexible
- ✅ Logs de actividad
- ✅ Notificaciones por email
- ✅ Soporte para webhooks

## 🎯 Demo

Puedes ver una demostración en vivo del sistema:

- **Formulario**: [Ver Demo](https://dnns.es/demo/contacto)
- **Panel Admin**: [Ver Demo](https://dnns.es/demo/contacto/ver-mensajes.php) (Contraseña: `admin123`)

### Capturas de pantalla

<details>
<summary>Ver capturas de pantalla</summary>

#### Formulario de Contacto
![Formulario](https://via.placeholder.com/800x400?text=Formulario+de+Contacto)

#### Panel de Administración
![Panel Admin](https://via.placeholder.com/800x400?text=Panel+de+Administracion)

</details>

## 🚀 Instalación

### Método 1: Instalación Automática (Recomendado) ⭐

1. **Descarga el instalador automático**
   ```
   https://github.com/Oberluss/contacto/releases/latest/download/instalar-desde-github.php
   ```

2. **Sube el archivo a tu servidor**
   - Súbelo a la carpeta donde quieres instalar el sistema (ej: `/public_html/contacto/`)

3. **Ejecuta el instalador**
   - Accede a: `https://tu-sitio.com/contacto/instalar-desde-github.php`
   - Sigue las instrucciones en pantalla
   - ¡Listo! El instalador descargará todo automáticamente

4. **Elimina el instalador** (por seguridad)

### Método 2: Instalación Manual

1. **Clona o descarga el repositorio**
   ```bash
   git clone https://github.com/Oberluss/contacto.git
   cd contacto
   ```

2. **Sube los archivos a tu servidor**
   ```
   - index.html
   - procesar-contacto.php
   - ver-mensajes.php
   ```

3. **Configura la contraseña**
   - Edita `ver-mensajes.php`
   - Busca la línea: `$password_admin = 'admin123';`
   - Cambia `admin123` por tu contraseña

4. **Crea los archivos necesarios**
   ```bash
   touch contactos.txt comentarios.txt
   chmod 644 contactos.txt comentarios.txt
   ```

5. **Crea el archivo .htaccess**
   ```apache
   <FilesMatch "\.(txt|log)$">
       Order Allow,Deny
       Deny from all
   </FilesMatch>
   ```

### Método 3: Instalación con Composer (Próximamente)

```bash
composer create-project oberluss/contacto mi-contacto
```

## 📖 Uso

### Para los visitantes

1. Acceden al formulario en `tu-sitio.com/contacto/`
2. Completan los campos requeridos
3. Envían el mensaje
4. Reciben confirmación instantánea

### Para el administrador

1. Accede a `tu-sitio.com/contacto/ver-mensajes.php`
2. Ingresa tu contraseña
3. Gestiona todos los mensajes recibidos
4. Usa la búsqueda para encontrar mensajes específicos

## ⚙️ Configuración

### Configuración Básica

Crea un archivo `config.php` con tus preferencias:

```php
<?php
return [
    // Contraseña del panel
    'admin_password' => 'tu_contraseña_segura',
    
    // Email para notificaciones
    'admin_email' => 'tu@email.com',
    'send_notifications' => true,
    
    // Límite de mensajes por IP por día
    'rate_limit' => 10,
    
    // Zona horaria
    'timezone' => 'America/Mexico_City'
];
?>
```

### Configuración Avanzada

<details>
<summary>Ver opciones avanzadas</summary>

```php
<?php
return [
    // Webhook para notificaciones
    'webhook_url' => 'https://discord.com/api/webhooks/...',
    'webhook_type' => 'discord', // discord, slack, telegram
    
    // Mensajes personalizados
    'messages' => [
        'success' => 'Tu mensaje personalizado de éxito',
        'error' => 'Tu mensaje personalizado de error'
    ],
    
    // Archivos
    'contacts_file' => 'mis_contactos.txt',
    'backup_dir' => 'backups/',
    
    // Seguridad
    'blocked_ips' => ['192.168.1.100'],
    'enable_captcha' => false
];
?>
```

</details>

## 🎨 Personalización

### Cambiar colores

Edita las variables CSS en `index.html`:

```css
:root {
    --primary-color: #4CAF50;  /* Tu color principal */
    --secondary-color: #2c3e50; /* Tu color secundario */
    --error-color: #e74c3c;     /* Color de error */
}
```

### Agregar campos

1. En `index.html`, agrega el nuevo campo:
```html
<div class="form-group">
    <label for="telefono">Teléfono</label>
    <input type="tel" id="telefono" name="telefono">
</div>
```

2. En `procesar-contacto.php`, procesa el campo:
```php
$telefono = sanitizar($_POST['telefono'] ?? '');
```

### Cambiar idioma

Todos los textos están centralizados. Busca y reemplaza en los archivos:
- "Contáctanos" → "Contact Us"
- "Enviar mensaje" → "Send Message"
- etc.

## 🔌 Integraciones

### Email HTML

El sistema soporta notificaciones por email HTML. Configura en `config.php`:

```php
'send_notifications' => true,
'admin_email' => 'tu@email.com'
```

### Webhooks

Integra con servicios externos:

- **Discord**: Notificaciones instantáneas en tu servidor
- **Slack**: Mensajes en tu workspace
- **Telegram**: Alertas en tu chat
- **Zapier**: Conecta con 3000+ apps

### API (Próximamente)

```bash
GET  /api/messages     # Lista mensajes
POST /api/messages     # Crear mensaje
DELETE /api/messages/1 # Eliminar mensaje
```

## 🛠️ Solución de Problemas

### Error: "No se pudo guardar el mensaje"

**Causa**: Permisos de escritura
**Solución**: 
```bash
chmod 755 .
chmod 644 *.txt
```

### Error 403 en install.php

**Causa**: Hosting bloquea archivos "install"
**Solución**: Usa el instalador desde GitHub o renombra el archivo

### Los emails no se envían

**Causa**: Función mail() deshabilitada
**Solución**: Contacta a tu hosting o usa SMTP

<details>
<summary>Ver más problemas comunes</summary>

### Contraseña olvidada
1. Accede por FTP
2. Edita `ver-mensajes.php`
3. Cambia `$password_admin = 'nueva_contraseña';`

### Mensajes no se muestran
- Verifica que `contactos.txt` existe
- Comprueba permisos del archivo
- Revisa el formato del archivo

</details>

## 📁 Estructura del Proyecto

```
contacto/
├── 📄 index.html              # Formulario de contacto
├── 📄 procesar-contacto.php   # Procesador de mensajes
├── 📄 ver-mensajes.php        # Panel de administración
├── 📄 README.md               # Este archivo
├── 📄 LICENSE                 # Licencia MIT
├── 📄 .gitignore              # Archivos ignorados por Git
├── 📄 contactos.txt           # Base de datos de mensajes (generado)
├── 📄 comentarios.txt         # Log de comentarios (generado)
├── 📄 config.php              # Configuración (generado)
└── 📁 backups/                # Carpeta de respaldos (generado)
```

## 🤝 Contribuir

¡Las contribuciones son bienvenidas! Por favor:

1. Fork el proyecto
2. Crea tu rama (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

### Guía de contribución

- Sigue el estilo de código existente
- Agrega comentarios en español
- Actualiza la documentación si es necesario
- Asegúrate de que todo funcione antes del PR

## 🔒 Seguridad

Si encuentras una vulnerabilidad de seguridad, por favor NO la reportes públicamente. Envía un email a: seguridad@tu-email.com

### Mejores prácticas implementadas

- ✅ Sanitización de todas las entradas
- ✅ Validación del lado del servidor
- ✅ Protección contra XSS
- ✅ Rate limiting
- ✅ Archivos sensibles protegidos

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

```
MIT License

Copyright (c) 2024 Oberluss

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction...
```

## 👥 Autores

- **Oberluss** - *Trabajo inicial* - [GitHub](https://github.com/Oberluss)

### Colaboradores

¡Gracias a todos los que han contribuido!

<!-- ALL-CONTRIBUTORS-LIST:START -->
<!-- ALL-CONTRIBUTORS-LIST:END -->

## 🙏 Agradecimientos

- A todos los que usan y mejoran este sistema
- A la comunidad PHP por su apoyo
- A los testers que reportan bugs

## 📞 Soporte

- 📧 Email: soporte@tu-email.com
- 💬 Issues: [GitHub Issues](https://github.com/Oberluss/contacto/issues)
- 🌐 Sitio web: [tu-sitio.com](https://tu-sitio.com)
- 📖 Wiki: [GitHub Wiki](https://github.com/Oberluss/contacto/wiki)

## 🔄 Changelog

### [1.0.0] - 2024-01-XX
- 🎉 Lanzamiento inicial
- ✨ Formulario de contacto responsivo
- ✨ Panel de administración
- ✨ Instalador automático desde GitHub
- 🔒 Seguridad mejorada
- 📝 Documentación completa

### Próximas versiones
- [ ] API REST
- [ ] Soporte multiidioma
- [ ] Exportación a Excel/PDF
- [ ] Plantillas de email
- [ ] Dashboard con gráficos
- [ ] Integración con CRM

---

<p align="center">
  Hecho con ❤️ por <a href="https://github.com/Oberluss">Oberluss</a>
</p>

<p align="center">
  ⭐ Si te gusta este proyecto, dale una estrella en GitHub ⭐
</p>
