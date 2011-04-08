{include file="findInclude:common/templates/header.tpl" customHeader=''}

<div class="nonfocal">
  {include file="findInclude:modules/{$moduleID}/templates/banner.tpl"}
  
  <h3>This graduation class has separate reunions for Harvard and Radcliffe.  Please select which reunion you will be attending:</h3>
  
  <form id="signin" name="signin" action="login" method="POST" onsubmit="return validateSelectCollegeForm();">
    <input type="hidden" name="authority" value="harris" />
    <input type="hidden" name="url" value="{$url|escape}" />
    
    <p>
      <select id="collegeIndex" name="collegeIndex">
        <option value="" selected>Please select your college</option>
        <option value="0">Harvard</option>
        <option value="1">Radcliffe</option>
      </select>
    </p>
    
    <div class="signinbuttons">
      <input class="signinbutton submit" name="login_submit" type="submit" value="Enter"/>
      <a class="signinbutton cancel" href="{$cancelURL}">Cancel</a>
    </div>
  </form>
  
  <div class="clear"></div>
</div>

{include file="findInclude:common/templates/footer.tpl"}
