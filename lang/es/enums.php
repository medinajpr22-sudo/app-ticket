<?php

return [

    'role_slug' => [
        'SuperAdmin' => 'Super administrador',
        'Admin' => 'Administrador',
        'User' => 'Usuario',
    ],

    'role_slug_description' => [
        'SuperAdmin' => 'Configura toda la plataforma: usuarios, equipos, categorías, SLA y seguridad.',
        'Admin' => 'Personal de TI. Atiende los tickets de sus equipos; quien lidera un equipo agrega auxiliares y sigue su rendimiento.',
        'User' => 'Colaborador. Crea tickets y hace seguimiento a sus solicitudes.',
    ],

    'permission_slug' => [
        'CreateTickets' => 'Crear tickets',
        'ViewOwnTickets' => 'Ver sus propios tickets',
        'ViewTeamTickets' => 'Ver los tickets de sus equipos',
        'ViewAllTickets' => 'Ver todos los tickets',
        'ReplyToTickets' => 'Responder tickets',
        'TakeTickets' => 'Tomar tickets',
        'AssignTickets' => 'Asignar y reasignar tickets',
        'ChangeTicketStatus' => 'Cambiar el estado de los tickets',
        'ChangeTicketPriority' => 'Cambiar la prioridad de los tickets',
        'ViewReports' => 'Ver reportes',
        'LeadTeams' => 'Liderar sus equipos: agregar auxiliares y ver su rendimiento',
        'ManageUsers' => 'Gestionar usuarios',
        'ManageTeams' => 'Gestionar equipos',
        'ManageCategories' => 'Gestionar categorías',
        'ManageSlaPolicies' => 'Gestionar SLA y horarios',
        'ManageMessageTemplates' => 'Gestionar plantillas de mensajes',
        'ManageRoles' => 'Gestionar roles y permisos',
        'ManageIdentityProviders' => 'Configurar inicio de sesión único (SSO)',
        'ViewAuditLogs' => 'Ver la auditoría',
    ],

    'permission_group' => [
        'Tickets' => 'Tickets',
        'Reports' => 'Reportes',
        'Leadership' => 'Liderazgo de equipos',
        'Management' => 'Administración de la plataforma',
    ],

    'team_type' => [
        'GeneralSupport' => 'Soporte general',
        'ApplicationSupport' => 'Consulta directa',
    ],

    'team_type_description' => [
        'GeneralSupport' => 'Equipos transversales de la mesa de ayuda: equipos, accesos, correo y redes.',
        'ApplicationSupport' => 'Equipos de aplicaciones: reciben la consulta sin pasar por la mesa general.',
    ],

    'ticket_priority' => [
        'Critical' => 'Crítica',
        'High' => 'Alta',
        'Medium' => 'Media',
        'Low' => 'Baja',
    ],

    'ticket_status' => [
        'Received' => 'Recibido',
        'InProgress' => 'En proceso',
        'WaitingOnRequester' => 'Esperando al usuario',
        'WaitingOnThirdParty' => 'Esperando a un tercero',
        'Resolved' => 'Resuelto',
        'Closed' => 'Cerrado',
        'Cancelled' => 'Cancelado',
    ],

    'sla_state' => [
        'OnTrack' => 'En plazo',
        'AtRisk' => 'En riesgo',
        'Breached' => 'Vencido',
        'Paused' => 'En pausa',
        'Met' => 'Cumplido',
        'Missed' => 'Incumplido',
    ],

    'ticket_channel' => [
        'Portal' => 'Portal',
        'Email' => 'Correo',
        'Phone' => 'Teléfono',
    ],

    'ticket_type' => [
        'Incident' => 'Incidente',
        'ServiceRequest' => 'Solicitud',
    ],

    'ticket_type_description' => [
        'Incident' => 'Algo dejó de funcionar y hay que restablecerlo.',
        'ServiceRequest' => 'La persona necesita algo nuevo: un acceso, un equipo o un cambio.',
    ],

    'message_template_purpose' => [
        'Greeting' => 'Saludo al tomar el ticket',
        'Acknowledgement' => 'Acuse de recibo',
        'AutoClose' => 'Cierre por falta de respuesta',
        'CannedResponse' => 'Respuesta rápida',
    ],

    'message_template_purpose_description' => [
        'Greeting' => 'Se envía al solicitante cuando un agente toma su ticket.',
        'Acknowledgement' => 'Se envía apenas se crea el ticket, antes de que alguien lo tome.',
        'AutoClose' => 'Se envía al cerrar un ticket que esperaba respuesta y no la recibió.',
        'CannedResponse' => 'El agente la elige al responder; puede editarla antes de enviar.',
    ],

    'template_variable' => [
        'Greeting' => 'Saludo según la hora',
        'RequesterFirstName' => 'Nombre del solicitante',
        'RequesterName' => 'Nombre completo del solicitante',
        'AgentName' => 'Nombre del agente',
        'TeamName' => 'Equipo que atiende',
        'CategoryName' => 'Categoría del ticket',
        'TicketNumber' => 'Número del ticket',
        'TicketSubject' => 'Asunto del ticket',
    ],

    'template_variable_sample' => [
        'Greeting' => 'Buenos días',
        'RequesterFirstName' => 'Carolina',
        'RequesterName' => 'Carolina Méndez',
        'AgentName' => 'Ana Torres',
        'TeamName' => 'Infraestructura y Hardware',
        'CategoryName' => 'Accesos y claves',
        'TicketNumber' => '1044',
        'TicketSubject' => 'Nuevo ingreso: crear usuario y correo',
    ],

    'weekday' => [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo',
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
