@php
    $selDept = old('department_id', $selected->department_id ?? '');
    $selProg = old('program_id', $selected->program_id ?? '');
    $selSec  = old('section_id', $selected->section_id ?? '');
    $sectionRequired = isset($sectionRequired) ? $sectionRequired : false;
@endphp

<style>
.hie-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.hie-field{min-width:0}
.hie-field label{display:block;margin-bottom:6px;font-size:.72rem;font-weight:750;color:var(--dash-text,#333)}
.hie-field label .required-mark{color:#fb7185}
.hie-field select{display:block;width:100%;min-height:40px;padding:10px 12px;border:1px solid var(--dash-line,#ddd);border-radius:9px;color:var(--dash-text,#333);background:var(--dash-surface-raised,#fff);font:inherit;font-size:.74rem;outline:none;transition:border-color .16s,box-shadow .16s}
.hie-field select:focus{border-color:#62c9f5;box-shadow:0 0 0 3px rgba(98,201,245,.12)}
.hie-field small{display:block;margin-top:5px;color:var(--dash-muted,#888);font-size:.61rem}
.hie-field .field-error{display:block;margin-top:4px;color:#fb7185;font-size:.64rem}
.hie-info-alert{grid-column:1/-1;display:flex;align-items:center;gap:8px;padding:10px 12px;margin-bottom:12px;border:1px solid rgba(98,201,245,.3);border-radius:9px;background:rgba(98,201,245,.08);font-size:.68rem;color:#0c4a6e}
.hie-info-alert i{color:#0ea5e9}
@media(max-width:620px){.hie-grid{grid-template-columns:1fr}}
</style>

<div class="hie-grid" id="hierarchyFields">
    @if($sectionRequired)
    <div class="hie-info-alert">
        <i class="fa-solid fa-circle-info"></i>
        <span><strong>Section required for automatic enrollment:</strong> Students will be automatically enrolled in all classes assigned to their section.</span>
    </div>
    @endif

    <div class="hie-field">
        <label for="department_id">Department</label>
        <select id="department_id" name="department_id" data-hie="department">
            <option value="">No Department</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" @selected((string) $selDept === (string) $dept->id)>{{ $dept->name }}</option>
            @endforeach
        </select>
        <small>Assign the user to a department (optional).</small>
        <span class="field-error">{{ $errors->first('department_id') }}</span>
    </div>

    <div class="hie-field">
        <label for="program_id">Program</label>
        <select id="program_id" name="program_id" data-hie="program">
            <option value="">No Program</option>
            @foreach($programs as $prog)
                <option value="{{ $prog->id }}" data-dept="{{ $prog->department_id ?? '' }}" @selected((string) $selProg === (string) $prog->id)>{{ $prog->name }}</option>
            @endforeach
        </select>
        <small>Program under the department (optional).</small>
        <span class="field-error">{{ $errors->first('program_id') }}</span>
    </div>

    <div class="hie-field">
        <label for="section_id">Section @if($sectionRequired)<span class="required-mark">*</span>@endif</label>
        <select id="section_id" name="section_id" data-hie="section" @if($sectionRequired) required @endif>
            <option value="">-- Select Section --</option>
            @foreach($sections as $sec)
                <option value="{{ $sec->id }}" data-prog="{{ $sec->program_id ?? '' }}" @selected((string) $selSec === (string) $sec->id)>{{ $sec->name }}@if($sec->code) ({{ $sec->code }})@endif</option>
            @endforeach
        </select>
        <small>Section under the program @if($sectionRequired)(required for automatic enrollment)@else(optional)@endif.</small>
        <span class="field-error">{{ $errors->first('section_id') }}</span>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const deptSel = document.getElementById('department_id');
    const progSel = document.getElementById('program_id');
    const secSel  = document.getElementById('section_id');
    if (!deptSel || !progSel || !secSel) return;

    const progOptions = Array.from(progSel.options).map(o => ({ value: o.value, dept: o.getAttribute('data-dept'), label: o.textContent }));
    const secOptions  = Array.from(secSel.options).map(o => ({ value: o.value, prog: o.getAttribute('data-prog'), label: o.textContent }));

    function filterPrograms() {
        const dept = deptSel.value;
        const previous = progSel.value;
        progSel.innerHTML = '';
        progSel.appendChild(new Option('No Program', ''));
        progOptions.forEach(function (p) {
            if (!p.value) return;
            if (dept && p.dept !== dept) return;
            progSel.appendChild(new Option(p.label, p.value));
        });
        const stillThere = Array.from(progSel.options).some(o => o.value === previous);
        progSel.value = stillThere ? previous : '';
        filterSections();
    }

    function filterSections() {
        const prog = progSel.value;
        const previous = secSel.value;
        secSel.innerHTML = '';
        secSel.appendChild(new Option('No Section', ''));
        secOptions.forEach(function (s) {
            if (!s.value) return;
            if (prog && s.prog !== prog) return;
            secSel.appendChild(new Option(s.label, s.value));
        });
        const stillThere = Array.from(secSel.options).some(o => o.value === previous);
        secSel.value = stillThere ? previous : '';
    }

    deptSel.addEventListener('change', filterPrograms);
    progSel.addEventListener('change', filterSections);
    filterPrograms();
})();
</script>
@endpush
