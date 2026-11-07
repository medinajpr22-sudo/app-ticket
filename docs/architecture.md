# Arquitectura · Mesa de Ayuda TI

Monolito modular en Laravel 13 + Inertia 2 + React 19 (TypeScript) + Tailwind 4.
El código está en inglés; la interfaz, en español (`lang/es`).

## Roles

| Rol (`slug`)  | Quién es                 | Qué hace                                                         |
| ------------- | ------------------------ | ---------------------------------------------------------------- |
| `user`        | Colaborador              | Crea tickets y sigue los suyos.                                  |
| `admin`       | Personal de TI           | Atiende los tickets de los equipos a los que pertenece.          |
| `super_admin` | Dirección TIC            | Todo lo anterior y configura la plataforma.                      |

El liderazgo no es un rol global: es `team_user.is_lead`, por equipo.

Los permisos de `admin` y `user` se editan en **Roles y permisos**. Por seguridad:

- `roles.manage` e `identity_providers.manage` están reservados al super admin
  (`PermissionSlug::isReservedForSuperAdmin()`): no se pueden delegar y, aunque
  aparecieran en la base de datos para otro rol, `User::hasPermission()` los ignora.
- Si se delega la gestión de usuarios, solo un super admin puede asignar el rol
  super admin o editar a un super admin (`UserPolicy`).

Cada permiso (`App\Domain\Identity\Enums\PermissionSlug`) se registra como un *gate*
(`can:users.manage`). El super admin pasa todos los gates (`Gate::before`).
Los permisos por defecto de cada rol están en `PermissionSlug::defaultsFor()` y los
siembra `RolePermissionSeeder`, que nunca quita permisos otorgados después.

## Estructura

```
app/
├── Domain/                     Lógica de negocio, agrupada por dominio
│   ├── Identity/               Usuarios, roles y permisos
│   ├── Organization/           Departamentos, sedes y equipos de TI
│   ├── Catalog/                Categorías de atención
│   ├── ServiceLevel/           Políticas de SLA, horarios laborales y festivos
│   ├── Ticketing/              Plantillas de mensajes, variables, prioridad y tipo de ticket
│   └── Shared/                 Piezas usadas por varios dominios (Icon, HasTranslatedLabel, HasSingleDefault)
│       (cada dominio: Actions/ Enums/ Listeners/ Models/ …)
├── Http/
│   ├── Controllers/Management/ Área de configuración (super admin)
│   ├── Requests/Management/    Validación de cada formulario
│   ├── Resources/              Lo único que llega a React: nunca se envía un modelo completo
│   └── Middleware/
routes/
├── web.php                     Inicio y perfil
├── auth.php                    Inicio de sesión (sin registro público)
└── management.php              /management/* — cada recurso protegido por su permiso
resources/js/
├── Components/ui/              Sistema de diseño (botones, campos, tarjetas, iconos…)
├── Components/{Dominio}/       Piezas de negocio reutilizables (Ticketing/PriorityBadge)
├── Layouts/                    AppLayout (barra lateral + superior) y GuestLayout
├── Pages/{Area}/{Recurso}/     Index, Create, Edit y Partials/
├── lib/                        Utilidades sin interfaz (classNames, formatDuration, formSubmitOptions)
└── types/                      Tipos que reflejan los Resources de Laravel
```

Áreas: `Portal` (colaboradores), `Desk` (personal de TI) y `Management` (configuración).
Se nombran por función y no por rol, para poder delegar permisos sin mover rutas.

### Recorrido de una petición

`Ruta (can:permiso)` → `Controller` delgado → `FormRequest` (validación) →
`Action` del dominio (transacción) → `Model` → redirección con mensaje flash.
Las lecturas pasan por un `Resource` antes de llegar a la página de Inertia.

## Convenciones de nombres

- **Tablas:** plural y `snake_case`. Pivotes en singular y orden alfabético (`team_user`).
- **FK a usuarios:** por el papel de la persona (`requester_id`, `assignee_id`). En historiales,
  `user_id` es quien hizo la acción.
- **Booleanos** `is_` / `has_`; **fechas con hora** `_at`; **duraciones** con unidad (`_minutes`).
- **Enums:** lo que la lógica usa es un *backed enum* de PHP guardado como texto; lo que el
  negocio configura desde pantallas es una tabla. Las etiquetas salen de `lang/es/enums.php`.
- **Clases:** Actions verbo + sustantivo (`CreateUser`), eventos en pasado, requests
  `Store…Request` / `Update…Request`.
- **No se borra, se desactiva:** usuarios y equipos tienen `is_active`; no hay borrado lógico.
- **Rutas:** URL en `kebab-case`, nombres con puntos (`management.users.index`).
- **React:** componentes en PascalCase, variables y props en camelCase, sin abreviaturas.

## Base de datos (implementada)

```mermaid
erDiagram
  roles ||--o{ users : "tiene"
  roles ||--o{ permission_role : "otorga"
  permissions ||--o{ permission_role : "pertenece"
  departments |o--o{ users : "agrupa"
  locations |o--o{ users : "ubica"
  users |o--o{ users : "jefe de"
  teams ||--o{ team_user : "integra"
  users ||--o{ team_user : "pertenece"

  roles {
    bigint id PK
    string slug UK "super_admin, admin, user"
  }
  permissions {
    bigint id PK
    string slug UK "users.manage"
  }
  permission_role {
    bigint permission_id PK, FK
    bigint role_id PK, FK
  }
  users {
    bigint id PK
    bigint role_id FK
    bigint department_id FK
    bigint location_id FK
    bigint manager_id FK
    string name
    string email UK
    string job_title
    string phone
    string password "null si entra por SSO"
    string external_id UK "id en Entra ID"
    boolean is_active
    timestamp last_login_at
  }
  departments {
    bigint id PK
    string name UK
  }
  locations {
    bigint id PK
    string name UK
    string address
  }
  teams {
    bigint id PK
    string name UK
    string type "general_support, application_support"
    string description
    string icon
    boolean is_active
  }
  team_user {
    bigint team_id PK, FK
    bigint user_id PK, FK
    boolean is_lead
  }
```

Catálogo y niveles de servicio:

```mermaid
erDiagram
  teams ||--|{ categories : "atiende"
  sla_policies |o--o{ categories : "define tiempos"
  sla_policies ||--|{ sla_targets : "por prioridad"
  business_calendars ||--o{ sla_policies : "usa horario"
  business_calendars ||--|{ business_hours : "dias y horas"
  business_calendars ||--o{ holidays : "festivos"

  categories {
    bigint id PK
    bigint team_id FK "unico con name"
    bigint sla_policy_id FK "null = politica predeterminada"
    string name
    string description
    string icon
    string default_ticket_type "incident, service_request"
    boolean is_visible
    smallint sort_order
  }
  sla_policies {
    bigint id PK
    bigint business_calendar_id FK
    string name UK
    string description
    boolean is_default "solo una"
  }
  sla_targets {
    bigint id PK
    bigint sla_policy_id FK "unico con priority"
    string priority "critical, high, medium, low"
    int first_response_minutes
    int resolution_minutes
  }
  business_calendars {
    bigint id PK
    string name UK
    string timezone
    boolean is_default "solo uno"
  }
  business_hours {
    bigint id PK
    bigint business_calendar_id FK
    tinyint weekday "1 lunes a 7 domingo"
    time opens_at
    time closes_at
  }
  holidays {
    bigint id PK
    bigint business_calendar_id FK "unico con date"
    string name
    date date
  }
```

Todas las tablas tienen `created_at` y `updated_at`.

Reglas de negocio de esta parte:

- Siempre hay exactamente un horario y una política predeterminados (`HasSingleDefault`).
  Marcar uno nuevo desmarca el anterior; el actual no se puede desmarcar directamente.
- Los minutos de SLA son minutos **laborales** del horario de la política.
- La resolución nunca puede tomar menos tiempo que la primera respuesta.
- Los nombres de categoría son únicos dentro de cada equipo, no en toda la plataforma.
- `ServiceLevelSeeder` crea el horario y la política predeterminados en cualquier ambiente.

Plantillas de mensajes:

```mermaid
erDiagram
  teams |o--o{ message_templates : "personaliza"
  users |o--o{ message_templates : "redacta"

  message_templates {
    bigint id PK
    bigint team_id FK "null = todos los equipos"
    bigint author_id FK "null = creada por el sistema"
    string purpose "greeting, acknowledgement, auto_close, canned_response"
    string name
    text body "con {{variables}}"
    boolean is_active
  }
```

- Los mensajes automáticos (`greeting`, `acknowledgement`, `auto_close`) admiten una plantilla
  activa general y una más por equipo; la del equipo reemplaza a la general.
- Las variables válidas están en `TemplateVariable`. `{{agent.name}}` no se permite en el acuse
  de recibo porque todavía no hay agente. `MessageTemplateSeeder` crea las tres generales.

Tickets:

```mermaid
erDiagram
  users ||--o{ tickets : "solicita y crea"
  users |o--o{ tickets : "atiende"
  teams ||--o{ tickets : "recibe"
  categories ||--o{ tickets : "clasifica"
  business_calendars ||--o{ tickets : "mide su SLA"
  tickets ||--|{ ticket_messages : "conversacion"
  tickets ||--o{ ticket_status_changes : "historial de estado"
  tickets ||--o{ ticket_assignments : "historial de asignacion"

  tickets {
    bigint id PK
    bigint requester_id FK
    bigint creator_id FK
    bigint team_id FK
    bigint category_id FK
    bigint assignee_id FK "null = sin asignar"
    bigint business_calendar_id FK "horario con el que se calculo el SLA"
    string type
    string status
    string priority
    string channel
    string subject
    timestamp first_response_due_at
    timestamp resolution_due_at
    timestamp first_responded_at
    timestamp sla_paused_at "mientras espera al usuario"
    timestamp resolved_at
    timestamp closed_at
  }
  ticket_messages {
    bigint id PK
    bigint ticket_id FK
    bigint author_id FK "null = automatico"
    string type "reply, internal_note, automated"
    text body
  }
  ticket_status_changes {
    bigint id PK
    bigint ticket_id FK
    bigint user_id FK "null = sistema"
    string from_status
    string to_status
  }
  ticket_assignments {
    bigint id PK
    bigint ticket_id FK
    bigint team_id FK
    bigint assignee_id FK
    bigint user_id FK "quien asigno"
  }
```

Flujo y reglas:

- **Crear (portal):** la categoría decide equipo, tipo y política de SLA. Las fechas de vencimiento
  se calculan en horas laborales con `BusinessTimeCalculator`. La descripción es el primer mensaje.
  El evento `TicketCreated` dispara `SendAcknowledgementMessage`.
- **Tomar (bandeja):** asignación atómica (`TakeTicket`): si dos agentes pulsan a la vez, solo uno
  gana. El evento `TicketTaken` dispara `SendGreetingMessage`, que envía el saludo del equipo en
  nombre del agente y cuenta como primera respuesta.
- **Responder:** hay que tomar el ticket antes de responder al solicitante; las notas internas no
  lo exigen y nunca llegan al portal. Si el solicitante responde a un ticket en espera o resuelto,
  vuelve a "En proceso".
- **SLA:** "Esperando al usuario" pausa el reloj (`sla_paused_at`). Al reanudar, las fechas
  pendientes se corren el tiempo laboral que estuvo en pausa. `TicketSlaTracker` indica si va en
  plazo, en riesgo (queda menos del 25 %), vencido, en pausa, cumplido o incumplido.
- **Permisos:** `TicketPolicy`. El solicitante solo ve sus tickets; el agente, los de sus equipos.
- **Adjuntos** (`ticket_attachments`, por mensaje): hasta 5 archivos de 10 MB, solo imágenes,
  PDF, Office, CSV y texto. Se guardan en el disco privado y se descargan por
  `attachments.show`, que revisa `TicketAttachmentPolicy`: los de notas internas son solo de TI.
- **Reasignar** (`AssignTicket`): a otro integrante activo del equipo; deja nota interna. Si nadie
  lo había tomado, se envía el saludo en nombre de esa persona.
- **Transferir** (`TransferTicket`): a otro equipo con motivo obligatorio; vuelve a "Recibido",
  sin responsable, y el solicitante recibe un aviso. Los vencimientos no cambian.
- **Cambiar prioridad** (`ChangeTicketPriority`, permiso `tickets.change_priority`): recalcula
  los vencimientos desde la creación con los tiempos de la nueva prioridad, más las pausas
  terminadas que registra el historial de estados.
- **Encuesta** (`ticket_ratings`, una por ticket): de 1 a 5 estrellas con comentario opcional,
  cuando el ticket está resuelto o cerrado. El panel general muestra el promedio de 30 días.

### Ajustes frente a la propuesta inicial

- `roles` y `permissions` solo guardan el `slug`: el nombre y la descripción salen de las
  traducciones del enum, así no se repite el mismo dato en dos lugares.
- `teams` y `categories` no llevan `slug`: las URL usan el `id` y el slug se derivaba del nombre.
- Se quitó `deleted_at` de usuarios, equipos y categorías: se usa solo `is_active`.

### Próximas tablas

Observadores de tickets, auditoría y SSO (`identity_providers`, `directory_groups`,
`directory_group_team`).
