<style>
    @media (min-width: 1024px) {
        .planes-estudio-admin-table .fi-ta-header-ctn {
            display: grid;
            grid-template-columns:
                minmax(0, 3fr)
                minmax(0, 3fr)
                minmax(0, 2fr)
                minmax(9rem, 2fr);
            column-gap: 0.75rem;
            padding: 1rem 1.5rem;
            align-items: end;
            border-bottom: 1px solid var(--gray-200);
        }

        .planes-estudio-admin-table .fi-ta-filters-above-content-ctn,
        .planes-estudio-admin-table .fi-ta-filters,
        .planes-estudio-admin-table .fi-ta-header-toolbar,
        .planes-estudio-admin-table .fi-ta-header-toolbar > :last-child {
            display: contents;
        }

        .planes-estudio-admin-table .fi-ta-filters-header {
            grid-column: 1 / -1;
            grid-row: 1;
            margin-bottom: 0.25rem;
        }

        .planes-estudio-admin-table .fi-ta-filters > .fi-sc {
            grid-column: 1 / 4;
            grid-row: 2;
            grid-template-columns:
                minmax(0, 3fr)
                minmax(0, 3fr)
                minmax(0, 2fr);
            align-self: end;
        }

        .planes-estudio-admin-table .fi-ta-filters-apply-action-ctn {
            grid-column: 4;
            grid-row: 2;
            display: grid;
            gap: 0.5rem;
            align-self: end;
        }

        .planes-estudio-admin-table .fi-ta-filters-apply-action-ctn .fi-btn {
            width: 100%;
            min-height: 2.5rem;
            justify-content: center;
        }

        .planes-estudio-admin-table .fi-ta-header-toolbar > :first-child:empty {
            display: none;
        }

        .dark .planes-estudio-admin-table .fi-ta-header-ctn {
            border-bottom-color: color-mix(in srgb, white 10%, transparent);
        }

    }
</style>
