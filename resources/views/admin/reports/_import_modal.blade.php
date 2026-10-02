<!-- Import Modal -->
<div class="modal" id="{{ $modalId }}">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-file-import"></i> Import {{ $importTitle }}</h3>
            <button type="button" class="modal-close" onclick="document.getElementById('{{ $modalId }}').classList.remove('active')">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ $importAction }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>CSV File</label>
                    <input type="file" name="file" accept=".csv,.txt" required class="form-control">
                    <small class="form-text">Upload a CSV. Expected columns:<br>{{ $importColumns }}</small>
                    @if(isset($importSample))
                        <small class="form-text" style="margin-top:6px;display:block">Sample:<br><code>{{ $importSample }}</code></small>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('{{ $modalId }}').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>
