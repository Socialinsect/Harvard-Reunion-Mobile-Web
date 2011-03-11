{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal" id="navbar2">
  comment - like - etc
</div>

<div class="video">
  <video controls>
    <source src="{$video['embed']}" type='video/mp4; codecs="avc1.42E01E, mp4a.40.2"'>
  </video>
</div>

<div class="nonfocal">
  <div class="message">{$video['message']}</div>
  <div class="smallprint">
    Uploaded {$video['when']['delta']} by 
    <a class="author" href="{$video['author']['url']}">{$video['author']['name']}</a>
  </div>
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

  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$video['comments']}
{/if}

{include file="findInclude:common/templates/footer.tpl"}
