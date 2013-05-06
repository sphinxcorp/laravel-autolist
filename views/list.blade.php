<table class="autolist">
    <caption>{{ $title }}</caption>
    <thead>
        <tr>
            @foreach ($header_columns as $attribute=>$header_column)
            <th>
                {{ $header_column }}
            </th>
            @endforeach
            @if($has_item_actions)
            <th>Actions</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
        <tr>
            @foreach (array_keys($header_columns) as $attribute)
            <td>{{ $item[$attribute] }}</td>
            @endforeach
            @if($has_item_actions)
            <td>{{ implode(' | ', $item['action_links']) }}</td>
            @endif
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($header_columns) + ($has_item_actions?1:0) }}">There are no items to show.</td>
        </tr>
        @endforelse
    </tbody>
    @if(!empty($global_action_links) || $page_links)
    <tfoot>
        <tr>
            <td colspan="{{ count($header_columns) + ($has_item_actions?1:0) -1 }}">
                {{ $page_links }}
            </td>
            <td>@if(!empty($global_action_links)) 
                {{ implode(' | ', $global_action_links) }}
                @endif
            </td>
        </tr>
    </tfoot>
    @endif
</table>