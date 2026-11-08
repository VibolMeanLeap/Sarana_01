@php($exportQuery = array_merge(request()->query(), ['type' => $exportType]))
<div class="cc-export-actions d-flex flex-wrap align-items-center gap-2">
    <a class="btn btn-sm btn-outline-success rounded-pill px-3" href="{{ route('admin.exports.excel', $exportQuery) }}" title="Download all matching filtered records as UTF-8 CSV (opens in Excel)">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export Excel
    </a>
    <a class="btn btn-sm btn-outline-danger rounded-pill px-3" href="{{ route('admin.exports.pdf', $exportQuery) }}" target="_blank" rel="noopener" title="Open a print-ready report; choose Save as PDF in the browser print dialog">
        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
    </a>
</div>
