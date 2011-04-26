{foreach $checkins as $type => $checkinsOfType}
  {if count($checkinsOfType)}
    {$list = array()}
    {foreach $checkinsOfType as $checkin}
      {$entry = array()}
      {$entry['class'] = "action external"}
      {$entry['url'] = "https://foursquare.com/mobile/user/{$checkin['id']}"}
      {capture name="title" assign="title"}
        {block name="checkinContent"}
          {if $checkin['photo']}
            <img class="fqPhoto" src="{$checkin['photo']}" height="20" width="20" /> 
          {/if}
          {$checkin['name']} 
          {if $checkin['message']}&ldquo;{$checkin['message']}&rdquo;{/if}
          <span class="smallprint"> - {$checkin['when']['shortDelta']}</span>
          {if !$checkin@last}<br/>{/if}
        {/block}
      {/capture}
      {$entry['title'] = $title}
      {$list[] = $entry}
    {/foreach}
    <div class="nonfocal">
      {if $type == 'friends'}
        Friends
      {elseif $type == 'others'}
        Others
      {/if}
    </div>
    {include file="findInclude:common/templates/navlist.tpl" navlistItems=$list accessKey=false linkTarget='reunionFoursquare'}
  {/if}
{/foreach}
