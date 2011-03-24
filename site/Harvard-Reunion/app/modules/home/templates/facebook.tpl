{include file="findInclude:common/templates/header.tpl" isModuleHome=true}

{if $needsLogin}
  {include file="findInclude:common/templates/needslogin.tpl" service=$service}

{elseif $needsJoinGroup}
  {include file="findInclude:common/templates/needsjoin.tpl" service=$service}
  
{else}
  <div class="nonfocal">
    <h2>{$groupName}</h2>
  </div>
  
  <div class="focal">
    <form method="get" action="add">
      <a class="scrolllink" name="postscrolldown"> </a>
      <textarea rows="2" name="message" id="messageText" placeholder="Write something..."></textarea>
      <input type="submit" value="Submit" onclick="return validateTextInputForm('messageText', 'Please enter a message to post to the Facebook group.');" />
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
  
  {$more = array()}
  {$more['title'] = '<span id="listFooter" class="fbpostsLink">More results...</span>'}
  {$more['url'] = $groupURL}
  {$more['class'] = 'external'}
  {$posts[] = $more}

  
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$posts navlistID="listContainer"}
  
  <div class="nonfocal">
    <span class="smallprint">Signed in as {$user} (<a href="{$switchUserURL}">change</a>)</span>
  </div>
{/if}

{include file="findInclude:common/templates/footer.tpl"}
