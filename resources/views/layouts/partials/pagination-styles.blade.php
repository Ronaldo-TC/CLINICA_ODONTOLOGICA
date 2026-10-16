<style>
    .table-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        width: 100%;
        padding: 16px 22px;
        border-top: 1px solid var(--line);
        background: var(--surface-soft);
        color: var(--ink);
        font-size: 12.5px;
        line-height: 1.5;
    }

    .table-pagination__summary {
        margin: 0;
        color: var(--muted);
    }

    .table-pagination__summary strong {
        color: var(--ink);
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .table-pagination__page-status {
        display: block;
        margin-top: 2px;
        font-size: 11.5px;
    }

    .table-pagination__controls {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        max-width: 100%;
    }

    .table-pagination__pages {
        display: flex;
        align-items: center;
        gap: 6px;
        min-width: 0;
        margin: 0;
        padding: 3px;
        overflow-x: auto;
        list-style: none;
        scrollbar-width: thin;
        scrollbar-color: var(--line) transparent;
    }

    .table-pagination__pages li {
        flex-shrink: 0;
    }

    .table-pagination .table-pagination__link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        flex-shrink: 0;
        min-width: 38px;
        min-height: 38px;
        padding: 8px 11px;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        background: var(--surface);
        color: var(--ink);
        font-size: 12.5px;
        font-weight: 500;
        line-height: 1;
        font-variant-numeric: tabular-nums;
        text-decoration: none;
        white-space: nowrap;
        transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
    }

    .table-pagination a.table-pagination__link:hover {
        border-color: var(--teal);
        background: color-mix(in srgb, var(--teal) 9%, var(--surface));
        color: var(--teal);
    }

    .table-pagination a.table-pagination__link:focus-visible {
        outline: 2px solid var(--teal);
        outline-offset: 2px;
    }

    .table-pagination .table-pagination__link.is-current {
        border-color: var(--teal);
        background: var(--teal);
        color: #fff;
        font-weight: 700;
    }

    .table-pagination .table-pagination__link[aria-disabled="true"] {
        background: transparent;
        color: var(--muted);
        opacity: .6;
        cursor: default;
    }

    .table-pagination__link svg {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
    }

    .table-pagination__ellipsis {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 24px;
        color: var(--muted);
    }

    .card-body > .table-pagination {
        padding: 0;
        border-top: 0;
        background: transparent;
    }

    @media (max-width: 640px) {
        .table-pagination {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
            padding: 14px;
        }

        .table-pagination__summary {
            text-align: center;
        }

        .table-pagination__controls {
            justify-content: space-between;
            gap: 4px;
        }

        .table-pagination__pages {
            gap: 4px;
        }

        .table-pagination__separator,
        .table-pagination__page:not(.is-nearby) {
            display: none;
        }

        .table-pagination .table-pagination__link {
            min-width: 36px;
            min-height: 44px;
            padding: 8px;
            font-size: 12px;
        }
    }

    @media (max-width: 360px) {
        .table-pagination__direction svg {
            display: none;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .table-pagination .table-pagination__link {
            transition: none;
        }
    }
</style>
