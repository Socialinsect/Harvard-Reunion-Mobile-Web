{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal" id="navbar2">
  comment - like - etc
</div>

{if $video['embedHTML']}
  <div class="video">
    {$video['embedHTML']}
  </div>
{else}
  <div class="nonfocal videoInfo">
    <h2>Video not available</h2>
  </div>
{/if}

<div class="nonfocal videoInfo">
  <div class="message">{$video['message']}</div>
  <div class="smallprint">
    Uploaded {$video['when']['delta']} by 
    <a class="author" href="{$video['author']['url']}">{$video['author']['name']}</a>
  </div>
</div>

{foreach $video['comments'] as $i => $comment}
  {capture name="title" assign="title"}
    "{$comment['message']}" 
    <span class="smallprint">
      <a class="author" href="{$comment['author']['url']}">
        {$comment['author']['name']}
      </a>, {$comment['when']['delta']}
    </span>
  {/capture}
  {$video['comments'][$i]['title'] = $title}
{/foreach}

{capture name="addCommentHTML" assign="addCommentHTML"}
  <form method="get" action="comment">
    <textarea rows="2" name="comment" placeholder="Add a comment"></textarea>
    <input type="submit" value="Submit" />
    <input type="hidden" name="id" value="{$video['id']|escape:'url'}" />
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
{/capture}
{$addComment = array()}
{$addComment['title'] = $addCommentHTML}
{$video['comments'][] = $addComment}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$video['comments']}


{include file="findInclude:common/templates/footer.tpl"}
