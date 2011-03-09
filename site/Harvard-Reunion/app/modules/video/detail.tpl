
{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <div class="author"><a href="{$video['author']['url']}">{$video['author']['name']}</a></div>
  <div class="message">{$video['message']}</div>
  <div class="when smallprint">
    {$video['when']['delta']}
    {foreach $video['actions'] as $action} 
    &nbsp;-&nbsp;<a href="{$action['link']}">{$action['name']}</a>
    {/foreach}
  </div>
</div>

<div class="video">
  <video controls>
    <source src="{$video['embed']}" type='video/mp4; codecs="avc1.42E01E, mp4a.40.2"'>
  </video>
</div>

{if count($video['comments'])}
  {foreach $video['comments'] as $i => $comment}
    {capture name="title" assign="title"}
      <a class="author" href="{$comment['author']['url']}">{$comment['author']['name']}</a>
      &nbsp;{$comment['message']}
    {/capture}
    {$video['comments'][$i]['title'] = $title}
    {$video['comments'][$i]['subtitle'] = $comment['when']['delta']}
  {/foreach}

  {include file="findInclude:common/results.tpl" results=$video['comments']}
{/if}

{include file="findInclude:common/footer.tpl"}
