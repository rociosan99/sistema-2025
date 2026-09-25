<style>
    @media (min-width: 1024px) {
        .mis-clases-profesor-table .fi-ta-header-ctn {
            display: grid;
            grid-template-columns:
                minmax(0, 17fr)
                minmax(0, 17fr)
                minmax(0, 12fr)
                minmax(0, 12fr)
                minmax(0, 12fr)
                minmax(0, 20fr)
                minmax(7rem, 10fr);
            column-gap: 0.75rem;
            padding: 1rem 1.5rem;
            align-items: end;
            border-bottom: 1px solid var(--gray-200);
        }

        .mis-clases-profesor-table .fi-ta-filters-above-content-ctn,
        .mis-clases-profesor-table .fi-ta-filters,
        .mis-clases-profesor-table .fi-ta-header-toolbar,
        .mis-clases-profesor-table .fi-ta-header-toolbar > :last-child {
            display: contents;
        }

        .mis-clases-profesor-table .fi-ta-filters-header {
            grid-column: 1 / -1;
            grid-row: 1;
            margin-bottom: 0.25rem;
        }

        .mis-clases-profesor-table .fi-ta-filters > .fi-sc {
            grid-column: 1 / 6;
            grid-row: 2;
            grid-template-columns:
                minmax(0, 17fr)
                minmax(0, 17fr)
                minmax(0, 12fr)
                minmax(0, 12fr)
                minmax(0, 12fr);
            align-self: end;
        }

        .mis-clases-profesor-table .fi-ta-filters-apply-action-ctn {
            grid-column: 7;
            grid-row: 2;
            display: grid;
            gap: 0.5rem;
            align-self: end;
        }

        .mis-clases-profesor-table .fi-ta-filters-apply-action-ctn::before {
            content: 'Acción';
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 500;
            color: var(--gray-950);
        }

        .mis-clases-profesor-table .fi-ta-filters-apply-action-ctn .fi-btn {
            width: 100%;
            min-height: 2.5rem;
            justify-content: center;
        }

        .mis-clases-profesor-table .fi-ta-header-toolbar > :first-child:empty {
            display: none;
        }

        .mis-clases-profesor-table .fi-ta-search-field {
            grid-column: 6;
            grid-row: 2;
            display: grid;
            gap: 0.5rem;
            align-self: end;
            width: 100%;
        }

        .mis-clases-profesor-table .fi-ta-search-field > label.fi-sr-only {
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

        .mis-clases-profesor-table .fi-ta-search-field .fi-input-wrp {
            min-height: 2.5rem;
        }

        .dark .mis-clases-profesor-table .fi-ta-header-ctn {
            border-bottom-color: color-mix(in srgb, white 10%, transparent);
        }

        .dark .mis-clases-profesor-table .fi-ta-filters-apply-action-ctn::before,
        .dark .mis-clases-profesor-table .fi-ta-search-field > label.fi-sr-only {
            color: white;
        }
    }
</style>
