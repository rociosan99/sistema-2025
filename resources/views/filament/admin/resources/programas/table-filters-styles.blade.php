<style>
    @media (min-width: 1024px) {
        .programas-admin-table .fi-ta-header-ctn {
            display: grid;
            grid-template-columns:
                minmax(0, 18fr)
                minmax(0, 18fr)
                minmax(0, 18fr)
                minmax(0, 10fr)
                minmax(0, 24fr)
                minmax(7rem, 12fr);
            column-gap: 0.75rem;
            padding: 1rem 1.5rem;
            align-items: end;
            border-bottom: 1px solid var(--gray-200);
        }

        .programas-admin-table .fi-ta-filters-above-content-ctn,
        .programas-admin-table .fi-ta-filters,
        .programas-admin-table .fi-ta-header-toolbar,
        .programas-admin-table .fi-ta-header-toolbar > :last-child {
            display: contents;
        }

        .programas-admin-table .fi-ta-filters-header {
            grid-column: 1 / -1;
            grid-row: 1;
            margin-bottom: 0.25rem;
        }

        .programas-admin-table .fi-ta-filters > .fi-sc {
            grid-column: 1 / 5;
            grid-row: 2;
            grid-template-columns:
                minmax(0, 18fr)
                minmax(0, 18fr)
                minmax(0, 18fr)
                minmax(0, 10fr);
            align-self: end;
        }

        .programas-admin-table .fi-ta-filters-apply-action-ctn {
            grid-column: 6;
            grid-row: 2;
            display: grid;
            gap: 0.5rem;
            align-self: end;
        }

        .programas-admin-table .fi-ta-filters-apply-action-ctn::before {
            content: 'Acción';
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 500;
            color: var(--gray-950);
        }

        .programas-admin-table .fi-ta-filters-apply-action-ctn .fi-btn {
            width: 100%;
            min-height: 2.5rem;
            justify-content: center;
        }

        .programas-admin-table .fi-ta-header-toolbar > :first-child:empty {
            display: none;
        }

        .programas-admin-table .fi-ta-search-field {
            grid-column: 5;
            grid-row: 2;
            display: grid;
            gap: 0.5rem;
            align-self: end;
            width: 100%;
        }

        .programas-admin-table .fi-ta-search-field > label.fi-sr-only {
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

        .programas-admin-table .fi-ta-search-field .fi-input-wrp {
            min-height: 2.5rem;
        }

        .dark .programas-admin-table .fi-ta-header-ctn {
            border-bottom-color: color-mix(in srgb, white 10%, transparent);
        }

        .dark .programas-admin-table .fi-ta-filters-apply-action-ctn::before,
        .dark .programas-admin-table .fi-ta-search-field > label.fi-sr-only {
            color: white;
        }
    }
</style>
