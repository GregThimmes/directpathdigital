@extends('layouts.app', [
    'namePage' => 'Ad Campaigns',
    'class' => '',
    'activePage' => 'bulk',
])

@section('content')
<div class="header bg-primary pb-6">
    <div class="container-fluid">
        <div class="header-body">
            <div class="row align-items-center py-4">
                <div class="col-lg-6 col-7">
                    <h6 class="h2 text-white d-inline-block mb-0">Active Campaigns <span id="activeInd">(loading...)</span></h6>
                </div>
                <div class="col-lg-6 col-5 text-right">
                    <a href="/bulk/download" id="export" class="btn btn-sm btn-neutral" target="_blank">Export</a>
                    <a href="/bulk/upload" class="btn btn-sm btn-neutral">Bulk Upload</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt--6">
    <div class="row">
        <!-- Light table -->
        <div class="col">
            <div class="card">
            <!-- Card header -->
                <div class="table-responsive">
                    <div id="myGrid" style="height: 400px;width:100%;" class="ag-theme-material"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
    <script type="text/javascript" charset="utf-8">
    // specify the columns
    var columnDefs = [
      {headerName: "Campaign ID", field: "id", floatingFilter: true},
      {headerName: "Campaign Name", field: "name", floatingFilter: true},
      {headerName: "Budget Total", field: "budget_total", floatingFilter: true},
      {headerName: "Budget Daily", field: "budget_daily", floatingFilter: true},
      {headerName: "Budget Type", field: "budget_limiter_type"},
      {headerName: "Active", field: "is_active", floatingFilter: true},
      {headerName: "Start Date", field: "start_date", floatingFilter: true},
      {headerName: "End Date", field: "end_date", floatingFilter: true}
      //{headerName: "Offers", field: "price"},
      //{headerName: "Today's Cost", field: "cost_today, floatingFilter: true", floatingFilter: true},
      //{headerName: "Total Cost", field: "cost_total", floatingFilter: true},
      //{headerName: "Status", field: "is_active", floatingFilter: true},
      //{headerName: "Pricing Model", field: "pricing_model"},
      //{headerName: "Clicks/Impressions per Day", field: "clicks_daily"},
      //{headerName: "Clicks/Impressions per IP", field: "conversions_daily"},
      //{headerName: "Today's Clicks", field: "clicks_today"},
      //{headerName: "Today's Impressions", field: "impressions_today"}
    ];

    // specify the data
    var rowData = [];

    // let the grid know which columns and what data to use
    var gridOptions = {
        columnDefs: columnDefs,
        rowData: rowData,
        rowHeight: 40,
         headerHeight: 40,
        defaultColDef: {
            filter: true // set filtering on for all cols
        },
        onFirstDataRendered: onFirstDataRendered,
        overlayLoadingTemplate:'<span class="ag-overlay-loading-center">Please wait while your rows are loading</span>',
        overlayNoRowsTemplate:'<span style="padding: 10px; border: 2px solid #444; background: lightgoldenrodyellow;">No data was returned</span>',
    };

    function onFirstDataRendered(params) {
        params.api.sizeColumnsToFit();
        document.getElementById('activeInd').innerHTML = '('+params.api.getDisplayedRowCount()+')';
    }


    document.addEventListener('DOMContentLoaded', function () 
    {
        new agGrid.Grid(document.getElementById('myGrid'), gridOptions);
        gridOptions.api.showLoadingOverlay();
        var httpRequest = new XMLHttpRequest();
        httpRequest.open('GET', 'bulk/campaigns');
        httpRequest.send();
        httpRequest.onreadystatechange = function () 
        {
            if (httpRequest.readyState === 4 && httpRequest.status === 200) 
            {
                var httpResult = JSON.parse(httpRequest.response);
    
                if(httpRequest.length === 0)
                {
                  gridOptions.api.showNoRowsOverlay();
                }
                else
                {
                  gridOptions.api.hideOverlay();
                  gridOptions.api.setRowData(httpResult);
                }
                
            }
        };
    });
    
    
    /* document.addEventListener('DOMContentLoaded', function () 
    {
        var httpRequest = new XMLHttpRequest();
        httpRequest.open('GET', 'bulk/disable');
        httpRequest.send();
        httpRequest.onreadystatechange = function () 
        {
            if (httpRequest.readyState === 4 && httpRequest.status === 200) 
            {
                var httpResult = JSON.parse(httpRequest.response);
                if(httpRequest.length === 0)
                {
                    console.log(httpRequest);
                }
                else
                {
                  console.log('no rows');
                }
            }
        };
    }); */

  </script>
@endpush