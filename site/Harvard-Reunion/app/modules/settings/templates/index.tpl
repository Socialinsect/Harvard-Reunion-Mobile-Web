{include file="findInclude:common/templates/header.tpl" scalable=false}

{$list = array()}
{$item = array()}
{block name="harrisLogin"}
  {capture name="title" assign="title"}
    {if $info['harris']['authority'] == 'anonymous'}
      You are viewing the class of {$info['harris']['year']} 
    {else}
      You are signed in as {$info['harris']['fullname']|escape} '{$info['harris']['shortYear']} 
    {/if}
    ({$info['harris']['number']}th&nbsp;Reunion) 
    {if $info['harris']['authority'] == 'anonymous'}anonymously {/if}
  {/capture}
  {$item['title'] = $title}
  {capture name="subtitle" assign="subtitle"}
    {if $info['harris']['authority'] == 'anonymous'}
      Tap to sign in
    {else}
      Tap to sign out
    {/if}
  {/capture}
  {$item['subtitle'] = $subtitle}
{/block}
{$item['url'] = $info['harris']['toggleURL']}
{$list[] = $item}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$list accessKey=false subTitleNewline=true labelColon=false}


<div class="nonfocal">
  <h3>Updates to show on home screen:</h3>
</div>

{$list = array()}
{$item = array()}
{capture name="title" assign="title"}
  {block name="twitterCheckbox"}
    <input type="checkbox" name="showTwitter" id="showTwitter" value="1" onchange="settingChanged(this)" {if $info['twitter']['showHome']}checked {/if}/> 
    <label for="showTwitter">Twitter Stream</label> 
    <span class="smallprint">({$info['twitter']['hashtag']|escape})</span>
  {/block}
{/capture}
{$item['title'] = $title}
{$list[] = $item}
{$item = array()}
{capture name="title" assign="title"}
  {block name="facebookCheckbox"}
    <input type="checkbox" name="showFacebook" id="showFacebook" value="1" onchange="settingChanged(this)" {if $info['facebook']['showHome']}checked {/if}/> 
    <label for="showFacebook">
      Updates from Facebook Group <div class="smallprint">({$info['facebook']['groupName']|escape})</div>
    </label>
  {/block}
{/capture}
{$item['title'] = $title}
{$list[] = $item}

{block name="formsubmit"}
{/block}

<form name="homescreenFeeds" method="get" action="change">
  {include file="findInclude:common/templates/navlist.tpl" navlistItems=$list accessKey=false subTitleNewline=true labelColon=false}

  {block name="formsubmit2"}
  {/block}
  
</form>

<div class="nonfocal">
  <h3>Third party services:</h3>
</div>

{$list = array()}
{$item = array()}
{block name="facebookLogin"}
  {$item['title'] = 'Facebook'}
  {capture name="subtitle" assign="subtitle"}
    {if $info['facebook']['username']}
      <span class="loggedin">Signed in as {$info['facebook']['username']}</span>
    {else}
      <span class="loggedout">Not signed in</span>
    {/if}
    <br/>
    Used for photos, videos and updates
    <br/>
    {if $info['facebook']['username']|escape}
      Tap to sign out
    {else}
      Tap to sign in
    {/if}
  {/capture}
  {$item['subtitle'] = $subtitle}
{/block}
{$item['url'] = $info['facebook']['toggleURL']}
{$list[] = $item}

{if $foursquareEnabled}
  {$item = array()}
  {block name="foursquareLogin"}
    {$item['title'] = 'foursquare'}
    {capture name="subtitle" assign="subtitle"}
      {if $info['foursquare']['username']}
        <span class="loggedin">Signed in as {$info['foursquare']['username']}</span>
      {else}
        <span class="loggedout">Not signed in</span>
      {/if}
      <br/>
      Used for checkins
      <br/>
      {if $info['foursquare']['username']|escape}
        Tap to sign out
      {else}
        Tap to sign in
      {/if}
    {/capture}
    {$item['subtitle'] = $subtitle}
  {/block}
  {$item['url'] = $info['foursquare']['toggleURL']}
  {$list[] = $item}
{/if}

{include file="findInclude:common/templates/navlist.tpl" navlistItems=$list accessKey=false subTitleNewline=true labelColon=false}

{include file="findInclude:common/templates/footer.tpl"}
