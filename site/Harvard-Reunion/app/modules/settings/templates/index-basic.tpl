{extends file="findExtends:modules/$moduleID/templates/index.tpl"}

{block name="harrisLogin"}
  {capture name="label" assign="label"}
    You are signed in  
    {if $info['harris']['authority'] == 'anonymous'}
      anonymously as class of {$info['harris']['year']} 
    {else}
      as {$info['harris']['fullname']} '{$info['harris']['shortYear']} 
    {/if}
    ({$info['harris']['number']}th&nbsp;Reunion) 
  {/capture}
  {$item['label'] = $label}
  {capture name="title" assign="title"}
    <span class="smallprint">({if $info['authority'] == 'anonymous'}sign in{else}sign out{/if})</span>
  {/capture}
  {$item['title'] = $title}
{/block}

{block name="facebookCheckbox"}
  <input type="checkbox" name="showFacebook" id="showFacebook" value="1" {if $info['facebook']['showHome']}checked {/if}/> 
  <label for="showFacebook">
    Updates from Facebook Group <span class="smallprint">({$info['facebook']['groupName']})</span>
  </label>
{/block}

{block name="formsubmit"}
  {$item = array()}
  {capture name="title" assign="title"}
    <input type="submit" name="settingsubmit" value="Save" />
  {/capture}
  {$item['title'] = $title}
  {$list[] = $item}
{/block}

{block name="facebookLogin"}
  {capture name="label" assign="label"}
    Facebook<br/>
    {if $info['facebook']['username']}
      <span class="loggedin"> Signed in as {$info['facebook']['username']} </span>
    {else}
      <span class="loggedout"> Not signed in </span>
    {/if}
  {/capture}
  {$item['label'] = $label}
  {capture name="title" assign="title"}
    <span class="smallprint">
      ({if $info['facebook']['username']}sign out{else}sign in{/if})
    </span>
  {/capture}
  {$item['title'] = $title}
  {capture name="subtitle" assign="subtitle"}
    Used for photos, videos, updates and checkins<br/>
  {/capture}
  {$item['subtitle'] = $subtitle}
{/block}

{block name="foursquareLogin"}
  {capture name="label" assign="label"}
    foursquare<br/>
    {if $info['foursquare']['username']}
      <span class="loggedin"> Signed in as {$info['foursquare']['username']} </span>
    {else}
      <span class="loggedout"> Not signed in </span>
    {/if}
  {/capture}
  {$item['label'] = $label}
  {capture name="title" assign="title"}
    <span class="smallprint">
      ({if $info['foursquare']['username']}sign out{else}sign in{/if})
    </span>
  {/capture}
  {$item['title'] = $title}
  {capture name="subtitle" assign="subtitle"}
    
    Used for checkins
  {/capture}
  {$item['subtitle'] = $subtitle}
{/block}
