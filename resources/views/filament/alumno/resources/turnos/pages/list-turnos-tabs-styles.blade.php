<style>
    .turnos-alumno-tabs {
        min-width: 0;
        width: 100%;
    }

    .turnos-alumno-tabs > .fi-tabs {
        box-sizing: border-box;
        width: 100%;
        margin-inline: 0;
        justify-content: space-between;
        gap: 0.5rem;
        overflow-x: auto;
        background-color: var(--primary-600);
        scrollbar-width: thin;
    }

    .turnos-alumno-tabs > .fi-tabs > .fi-tabs-item {
        flex: 0 0 auto;
        background-color: transparent;
    }

    .turnos-alumno-tabs > .fi-tabs > .fi-tabs-item .fi-tabs-item-label {
        color: var(--color-white);
    }

    .turnos-alumno-tabs > .fi-tabs > .fi-tabs-item:hover,
    .turnos-alumno-tabs > .fi-tabs > .fi-tabs-item:focus-visible {
        background-color: color-mix(in srgb, var(--color-white) 12%, transparent);
    }

    .turnos-alumno-tabs > .fi-tabs > .fi-tabs-item.fi-active {
        background-color: var(--primary-800);
        box-shadow: inset 0 -2px 0 var(--primary-200);
    }

    .turnos-alumno-tabs > .fi-tabs > .fi-tabs-item.fi-active .fi-tabs-item-label {
        color: var(--color-white);
        font-weight: 600;
    }

    .turnos-alumno-tabs > .fi-tabs > .fi-tabs-item .fi-badge {
        background-color: color-mix(in srgb, var(--color-white) 18%, transparent);
        color: var(--color-white);
    }

    .turnos-alumno-tabs > .fi-tabs > .fi-tabs-item.fi-active .fi-badge {
        background-color: color-mix(in srgb, var(--color-white) 24%, transparent);
        color: var(--color-white);
    }

    .fi-resource-turnos .fi-ta-filters-above-content-ctn {
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }

    .fi-resource-turnos .fi-ta-filters {
        row-gap: 0.75rem;
    }

    .fi-resource-turnos .fi-ta-filters-apply-action-ctn {
        margin-top: -0.125rem;
    }

    @media (max-width: 63.999rem) {
        .turnos-alumno-tabs > .fi-tabs {
            justify-content: flex-start;
        }
    }
</style>
