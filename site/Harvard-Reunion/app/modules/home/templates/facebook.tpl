{include file="findInclude:common/templates/header.tpl" isModuleHome=true}

{if $needsLogin}
  {include file="findInclude:common/templates/needslogin.tpl" service=$service}

{elseif $needsJoinGroup}
  {include file="findInclude:common/templates/needsjoin.tpl" service=$service}
  
{else}
  <div class="nonfocal">
    <h2>{$groupName}</h2>
  </div>
  
  {foreach $posts as $i => $post}
    {capture name="title" assign="title"}
      &ldquo;{$post['message']}&rdquo; 
      <span class="smallprint"> -&nbsp;{$post['author']['name']}, {$post['when']['delta']}
      </span>
    {/capture}
    {$posts[$i]['title'] = $title}
  {/foreach}
  
  {capture name="addPostHTML" assign="addPostHTML"}
    <form method="get" action="add">
      <a class="scrolllink" name="postscrolldown"> </a>
      <textarea rows="2" name="message" placeholder="Write something..."></textarea>
      <input type="submit" value="Submit" />
      <input type="hidden" name="type" value="facebook" />
      {foreach $breadcrumbSamePageArgs as $arg => $value}
        <input type="hidden" name="{$arg}" value="{$value}" />
      {/foreach}
    </form>
  {/capture}
  {$addPost = array()}
  {$addPost['title'] = $addPostHTML}
  {$r = array_unshift($posts, $addPost)}
  
  {include file="findInclude:common/templates/results.tpl" results=$posts}
  
  <div class="nonfocal">
    <span class="smallprint">Signed in as {$user} (<a href="{$switchUserURL}">change</a>)</span>
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
