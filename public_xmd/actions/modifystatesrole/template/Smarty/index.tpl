{**
 *  \details &copy; 2018 Open Ximdex Evolution SL [http://www.ximdex.org]
 *
 *  Ximdex a Semantic Content Management System (CMS)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  See the Affero GNU General Public License for more details.
 *  You should have received a copy of the Affero GNU General Public License
 *  version 3 along with Ximdex (see LICENSE file).
 *
 *  If not, visit http://gnu.org/licenses/agpl-3.0.html.
 *
 *  @author Ximdex DevTeam <dev@ximdex.com>
 *  @version $Revision$
 *}

<form method="post" id="msr_action" ng-controller="XModifyStatesRoleCtrl">
    {include file="actions/components/title_Description.tpl"}
    <div ng-view ng-show="thereAreMessages" class="slide-item #/messageClass/# message">
        <p>#/message/#</p>
    </div>
    <div class="action_content" ng-init='idRole={$idRole}; workflows={$workflows};'>
        <div ng-repeat="workflow in workflows">
        <div class="row tarjeta">
            <div class="small-12 columns title_tarjeta">
                <h2 class="h2_general">{t}#/workflow.description/#{/t}</h2>
            </div>
            <div class="small-12 columns">
                <label class="label_title label_general">{t}Select the status asociated with the role{/t}</label>
                <div ng-init="wf_states=workflow.states;">
	               <fieldset>
	                   <p ng-repeat="state in wf_states">
	                       <span>
	                           <input type="checkbox" class="hidden-focus" id="#/state.name/#_#/idRole/#" ng-model="state.asociated" 
	                                   name="pepe" />
	                           <label for="#/state.name/#_#/idRole/#" class="checkbox-label icon">#/state.name/#</label>
	                       </span>
	                    </p>
	               </fieldset>
	            </div>
	        </div>
        </div>
      </div>
      <div class="small-12 columns">
          <fieldset ng-init="label='{t}Save changes{/t}'; loading=false;" class="buttons-form">
              <button class="btn main_action" xim-button xim-loading="loading" xim-label="label" xim-progress="" xim-disabled=""
                      ng-click="saveChanges();"></button>
          </fieldset>
      </div>
    </div>
</form>
