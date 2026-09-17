@props(['title', 'headers' => [], 'rows' => [], 'emptyMessage' => 'No data available'])

<div class="table-card">
  <h3>{{ $title }}</h3>
  <table class="data-table">
    <thead>
      <tr>
        @foreach($headers as $header)
          <th>{{ $header }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @if(count($rows) > 0)
        @foreach($rows as $row)
          <tr>
            @foreach($row as $cell)
              <td>{!! $cell !!}</td>
            @endforeach
          </tr>
        @endforeach
      @else
        <tr>
          <td colspan="{{ count($headers) }}" style="text-align:center;padding:24px;color:#aaa;">
            {{ $emptyMessage }}
          </td>
        </tr>
      @endif
    </tbody>
  </table>
</div>