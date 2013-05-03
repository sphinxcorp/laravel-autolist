<table class="autolist">
    <caption>{{ $title }}</caption>
    <thead>
        <tr>
            @foreach ($attributes as $attribute)
            <th>
                @render(Config::get('autolist::autolist.views.header_item'),$attribute)
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
            @foreach ($attributes as $attribute=>$attr_details)
            <td>{{ e($item->$attribute) }}</td>
            @endforeach
            @if($has_item_actions)
            <th>{{ implode(' | ', $item->action_links) }}</th>
            @endif
        </tr>
        @empty
        <tr>
            <td colspan="{{ count($attributes) + ($has_item_actions?1:0) }}">There are no items to show.</td>
        </tr>
        @endforelse
    </tbody>
    @if(!empty($global_action_links) || $page_links)
    <tfoot>
        <tr>
            <td colspan="{{ count($attributes) + ($has_item_actions?1:0) -1 }}">
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