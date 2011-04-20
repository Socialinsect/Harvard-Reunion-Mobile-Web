<div class="postNav" id="navbar2">
  {block name="postNavigation"}
    {if $post['prevURL']}
      <a class="postControl" href="{$post['prevURL']}"><div id="prev"></div></a>
    {/if}
    <a class="postControl" href="#commentscrolldown"><div id="comment"></div></a>
    <a class="postControl" onclick="alert('Click!');toggleBookmark('{$cookieName}', '{$post['id']}', {$expireDate}, '{$smarty.const.COOKIE_PATH}');"><div id="bookmark" {if $bookmarkStatus == 'on'}class="on"{/if}></div></a>
    <a class="postControl" href="{$post['likeURL']}"><div id="like"{if $post['liked']} class="liked"{/if}></div></a>
    {if $post['nextURL']}
      <a class="postControl" href="{$post['nextURL']}"><div id="next"></div></a>
    {/if}
  {/block}
</div>

{$post['html']}

<div class="nonfocal">
  <div class="message">{$post['message']}</div>
  <div class="smallprint">
    Uploaded {$post['when']['delta']} by 
    <a class="author" href="{$post['author']['url']}">{$post['author']['name']}</a>
    {if $post['liked'] || $post['otherLikes']}
      <br/>
      {if $post['liked']}
        You {if $post['otherLikes']}and {/if}
      {/if}
      {if $post['otherLikes']}
        {$post['otherLikes']} {if $post['otherLikes'] > 1}people {else}person {/if}
      {/if}
      like{if !$post['liked'] && $post['otherLikes'] == 1}s{/if} this {$post['typeString']}
    {/if}
  </div>
</div>

<div id="autoupdateContainer">
  {include file="findInclude:common/templates/postdetailContent.tpl" post=$post}
</div>
  
<div class="focal fbPostForm">
  <form method="get" action="comment">
    <a class="scrolllink" name="commentscrolldown"> </a>
    {block name="formelements"}
      <textarea rows="3" name="message" id="messageText" placeholder="Add a comment"></textarea>
      <input type="submit" value="Submit" onclick="return validateTextInputForm('messageText', 'Please enter a comment for this Facebook post.');" />
    {/block}
    <input type="hidden" name="id" value="{$post['id']|escape:'url'}" />
    <input type="hidden" name="view" value="{$currentView}" />
    <input type="hidden" name="action" value="add" />
    {foreach $breadcrumbSamePageArgs as $arg => $value}
      <input type="hidden" name="{$arg}" value="{$value}" />
    {/foreach}
  </form>
</div>
