{include file="findInclude:common/templates/header.tpl" isModuleHome=true}

{if $needsLogin}
  {include file="findInclude:common/templates/needslogin.tpl" service=$service}

{elseif $needsJoinGroup}
  {include file="findInclude:common/templates/needsjoin.tpl" service=$service}
  
{else}
  <div class="focal fbPostForm">
    <form method="get" action="add">
      <a class="scrolllink" name="postscrolldown"> </a>
      <textarea rows="3" name="message" id="messageText" placeholder="Share an update with the {$groupName} group"></textarea>
      <input type="submit" value="Share" onclick="return validateTextInputForm('messageText', 'Please enter a message to post to the Facebook group.');" />
      <input type="hidden" name="type" value="facebook" />
      {foreach $breadcrumbSamePageArgs as $arg => $value}
        <input type="hidden" name="{$arg}" value="{$value}" />
      {/foreach}
    </form>
  </div>
  
  {foreach $posts as $i => $post}
    {capture name="title" assign="title"}
      &ldquo;{$post['message']}&rdquo; 
      <span class="smallprint"> -&nbsp;{$post['author']['name']}, {$post['when']['delta']}</span>
    {/capture}
    {$posts[$i]['title'] = $title}
  {/foreach}
  
  {if !count($posts)}
    {$empty = array()}
    {$empty['title'] = 'No posts for '|cat:$groupName}
    {$posts[] = $empty}
  {/if}
  
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$posts navlistID="listContainer"}
  
  <div class="nonfocal">
    <span class="smallprint">Signed in as {$user} (<a href="{$switchUserURL}">change</a>)</span>
  </div>
  <div class="nonfocal">
    <span class="smallprint">View {$groupName} at <a href="{$groupURL}">facebook.com</a></span>
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
