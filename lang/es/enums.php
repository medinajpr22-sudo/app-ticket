<?php

return [

    'role_slug' => [
        'SuperAdmin' => 'Super administrador',
        'Admin' => 'Administrador',
        'User' => 'Usuario',
    ],

    'role_slug_description' => [
        'SuperAdmin' => 'Configura toda la plataforma: usuarios, equipos, categorías, SLA y seguridad.',
        'Admin' => 'Personal de TI. Atiende los tickets de los equipos a los que pertenece.',
        'User' => 'Colaborador. Crea tickets y hace seguimiento a sus solicitudes.',
    ],

    'permission_slug' => [
        'CreateTickets' => 'Crear tickets',
        'ViewOwnTickets' => 'Ver sus propios tickets',
        'ViewTeamTickets' => 'Ver los tickets de sus equipos',
        'ViewAllTickets' => 'Ver todos los tickets',
        'ReplyToTickets' => 'Responder tickets',
        'AddInternalNotes' => 'Escribir notas internas',
        'TakeTickets' => 'Tomar tickets',
        'AssignTickets' => 'Asignar y reasignar tickets',
        'ChangeTicketStatus' => 'Cambiar el estado de los tickets',
        'ViewReports' => 'Ver reportes',
        'ManageUsers' => 'Gestionar usuarios',
        'ManageTeams' => 'Gestionar equipos',
        'ManageCategories' => 'Gestionar categorías',
        'ManageSlaPolicies' => 'Gestionar SLA y horarios',
        'ManageMessageTemplates' => 'Gestionar plantillas de mensajes',
        'ManageRoles' => 'Gestionar roles y permisos',
        'ManageIdentityProviders' => 'Configurar inicio de sesión único (SSO)',
        'ViewAuditLogs' => 'Ver la auditoría',
    ],

    'team_type' => [
        'GeneralSupport' => 'Soporte general',
        'ApplicationSupport' => 'Consulta directa',
    ],

    'team_type_description' => [
        'GeneralSupport' => 'Equipos transversales de la mesa de ayuda: equipos, accesos, correo y redes.',
        'ApplicationSupport' => 'Equipos de aplicaciones: reciben la consulta sin pasar por la mesa general.',
    ],

    'icon' => [
        'Laptop' => 'Equipo',
        'Key' => 'Accesos',
        'Puzzle' => 'Software',
        'Wifi' => 'Red',
        'Mail' => 'Correo',
        'Help' => 'Ayuda',
        'Printer' => 'Impresora',
        'Video' => 'Video',
        'Pulse' => 'Pulso',
        'Drop' => 'Gota',
        'Grid' => 'Aplicación',
        'Spark' => 'IA',
        'Chart' => 'Gráfico',
        'Server' => 'Servidor',
        'Users' => 'Personas',
        'Shield' => 'Seguridad',
        'Clock' => 'Agenda',
        'Search' => 'Búsqueda',
    ],

];
