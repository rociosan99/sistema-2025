<?php

return [
    // =========================
    // Matching batch (solicitudes -> ofertas al profesor) (legacy / existente)
    // =========================
    'max_offers_per_batch' => 3,
    'offer_ttl_minutes'    => 60,

    // =========================
    // Reemplazo por cancelación (slot liberado)
    // =========================

    // ✅ ventana donde NO se publica en slots generales
    // mientras el sistema intenta reemplazo
    'replacement_window_minutes' => 60,

    // ✅ cuánto tiempo tiene el alumno para aceptar una invitación de reemplazo
    // (después de este tiempo, se considera que no hubo reemplazo)
    'replacement_invite_ttl_minutes' => 30,

    // ✅ máximo de alumnos a los que se invita por reemplazo
    // (para no spamear / no generar muchas invitaciones)
    'replacement_max_invites' => 10,
];