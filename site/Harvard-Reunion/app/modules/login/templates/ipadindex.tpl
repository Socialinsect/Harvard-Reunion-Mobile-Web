{include file="findInclude:common/templates/header.tpl" customHeader='' scalable=false}

<div class="nonfocal">
  <div class="ipadReunionHeader">
    <h1>Harvard/Radcliffe College Reunions</h1>
    <p id="intro">Veritas Mobile: Your Reunion Connection. View your personalized schedule, maps, news, photos, and more.</p>
  </div>
  
</div>

<div class="loginBlock">
  <div class="columns">
    <div class="harris">
      <div class="nonfocal">
        <h3>Sign in with your alumni login</h3>
        <p class="smallprint">
          {if $authFailed}
            We're sorry, but there was a problem with your login. Please check your user ID and password (the same login you use at alumni.harvard.edu) and try again.
          {else}
            This unlocks private features of the application.
          {/if}
        </p>
      
        <form id="signin" name="signin" action="login" method="POST">
          {foreach $defaultArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value}" />
          {/foreach}
          <input type="hidden" name="authority" value="harris" />
        
          {block name="inputs"}
            <p><label for="username">User ID:</label>
            <input type="text" id="username" name="loginUser" />
            </p>
        
            <p><label for="pwd">Password:</label>
            <input type="password" id="pwd" name="loginPassword" />
            </p>
          {/block}
          
          {include file="findInclude:modules/{$moduleID}/templates/buttons.tpl" submitText="Sign In" hasCancel=false}
        </form>
      
        <div class="clear"></div>
      </div>
    </div>
    
    <div class="or"><div class="nonfocal">or</div></div>
    
    <div class="anonymous">
      <div class="nonfocal">
        <h3>Just select your graduation year</h3>
        <p class="smallprint">Entering this app anonymously will lock you out of private areas and personalized features.</p>
    
        <form id="signin" name="signin" action="login" method="POST" onsubmit="return validateAnonymousForm();">
          {foreach $defaultArgs as $arg => $value}
            <input type="hidden" name="{$arg}" value="{$value}" />
          {/foreach}
          <input type="hidden" name="authority" value="anonymous" />
          <p>
            <select id="year" name="loginUser">
              <option value="" selected></option>
              {foreach $reunionYears as $config}
                {if $config['separate']}
                  <option value="{$config['year']}h">{$config['year']} ({$config['number']}th Harvard)</option>
                  <option value="{$config['year']}r">{$config['year']} ({$config['number']}th Radcliffe)</option>
                {else}
                  <option value="{$config['year']}">{$config['year']} ({$config['number']}th Reunion)</option>
                {/if}
              {/foreach}
            </select>
          </p>
        
          {include file="findInclude:modules/{$moduleID}/templates/buttons.tpl" submitText="Enter" hasCancel=false}
        </form>
      
        <div class="clear"></div>
      </div>
    </div>
  </div>

  <div class="helptext">
    <p>Not registered?  <a href="https://post.harvard.edu/olc/pub/HAA/register/register.cgi" target="reunionHarris">Sign up</a> for an alumni login.</p>
    <p><a href="https://post.harvard.edu/olc/pub/HAA/forgot/forgot.cgi" target="reunionHarris">Forgot password</a></p>
  </div>
</div>

<div class="nonfocal">
  {if ($platform == 'iphone' || $plaform == 'ipad') && !$suppressiOSLink}
    <div id="download"><a href="http://itunes.apple.com/us/app/harvard-mobile/id389199460"><img src="/common/images/signin-appstore.png" width="124" height="46" alt="Download">Get the native app for your iPad</a></div>
  {/if}
</div>

{include file="findInclude:common/templates/footer.tpl"}
