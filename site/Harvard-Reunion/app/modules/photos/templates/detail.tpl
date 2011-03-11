{include file="findInclude:common/templates/header.tpl"}

<div class="nonfocal" id="navbar2">
  <a id="comment" href="#comment">Comment</a>
  <a id="bookmark" onclick="toggleBookmark('{$name}', '{$photo['id']}', {$exdate}, '{$smarty.const.COOKIE_PATH}')">Bookmark</a>
  <a id="like" href="{$likeURL}">Like</a>  
</div>

<div class="photo">
  <img src="{$photo['img']['src']}" />
</div>

<div class="nonfocal">
  <div class="message">{$photo['message']}</div>
  <div class="smallprint">
    Uploaded {$photo['when']['delta']} by 
    <a class="author" href="{$photo['author']['url']}">{$photo['author']['name']}</a>
  </div>
</div>

{foreach $photo['comments'] as $i => $comment}
  {capture name="title" assign="title"}
    "{$comment['message']}" 
    <span class="smallprint">
      <a class="author" href="{$comment['author']['url']}">
        {$comment['author']['name']}
      </a>, {$comment['when']['delta']}
    </span>
  {/capture}
  {$photo['comments'][$i]['title'] = $title}
{/foreach}

{capture name="addCommentHTML" assign="addCommentHTML"}
  <form method="get" action="comment">
    <textarea rows="2" name="comment" placeholder="Add a comment"></textarea>
    <input type="submit" value="Submit" />
    <input type="hidden" name="id" value="{$photo['id']|escape:'url'}" />
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
{/capture}
{$addComment = array()}
{$addComment['title'] = $addCommentHTML}
{$photo['comments'][] = $addComment}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$photo['comments']}

{include file="findInclude:common/templates/footer.tpl"}