<style>
    @media (min-width: 1024px) {
        .usuarios-admin-table .fi-ta-header-ctn {
            display: grid;
            grid-template-columns:
                minmax(0, 22fr)
                minmax(0, 22fr)
                minmax(0, 40fr)
                minmax(8rem, 16fr);
            column-gap: 1rem;
            padding: 1rem 1.5rem;
            align-items: end;
            border-bottom: 1px solid var(--gray-200);
        }

        .usuarios-admin-table .fi-ta-filters-above-content-ctn {
            display: contents;
        }

        .usuarios-admin-table .fi-ta-filters {
            display: contents;
        }

        .usuarios-admin-table .fi-ta-filters-header {
            grid-column: 1 / -1;
            grid-row: 1;
            margin-bottom: 0.25rem;
        }

        .usuarios-admin-table .fi-ta-filters > .fi-sc {
            grid-column: 1 / 3;
            grid-row: 2;
            align-self: end;
        }

        .usuarios-admin-table .fi-ta-filters-apply-action-ctn {
            grid-column: 4;
            grid-row: 2;
            display: grid;
            gap: 0.5rem;
            align-self: end;
        }

        .usuarios-admin-table .fi-ta-filters-apply-action-ctn::before {
            content: 'Acción';
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 500;
            color: var(--gray-950);
        }

        .usuarios-admin-table .fi-ta-filters-apply-action-ctn .fi-btn {
            width: 100%;
            min-height: 2.5rem;
            justify-content: center;
        }

        .usuarios-admin-table .fi-ta-header-toolbar {
            display: contents;
        }

        .usuarios-admin-table .fi-ta-header-toolbar > :first-child:empty {
            display: none;
        }

        .usuarios-admin-table .fi-ta-header-toolbar > :last-child {
            display: contents;
        }

        .usuarios-admin-table .fi-ta-search-field {
            grid-column: 3;
            grid-row: 2;
            display: grid;
            gap: 0.5rem;
            align-self: end;
            width: 100%;
        }

        .usuarios-admin-table .fi-ta-search-field > label.fi-sr-only {
            position: static;
            width: auto;
            height: auto;
            padding: 0;
            margin: 0;
            overflow: visible;
            clip: auto;
            white-space: normal;
            border: 0;
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 500;
            color: var(--gray-950);
        }

        .usuarios-admin-table .fi-ta-search-field .fi-input-wrp {
            min-height: 2.5rem;
        }

        .dark .usuarios-admin-table .fi-ta-header-ctn {
            border-bottom-color: color-mix(in srgb, white 10%, transparent);
        }

        .dark .usuarios-admin-table .fi-ta-filters-apply-action-ctn::before,
        .dark .usuarios-admin-table .fi-ta-search-field > label.fi-sr-only {
            color: white;
        }
    }
</style>
