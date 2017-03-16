<?php
include 'html_include/html_top.php';
include 'html_include/menu.php';
include 'config/config.php';
include 'classes/OpenClinicaSoapWebService.php';

?>
<!--main content starts-->
<script type="text/javascript">
        jQuery( document ).ready(function() {

         	jQuery('body').on('click', '.clone_study', function () {
        		var id = this.id;
        		cloneStudyToProject(id);
            	});
           	
        	jQuery('body').on('click', '.projectRow', function () {
        		var id = this.id;
        		displayProjectDetails(id);
            	});
        	jQuery('body').on('click', '#getStudyMeta', function () {
        		getStudyMetaData();
            	});

        	jQuery('body').on('click', '#createEventsForms', function () {
        		createEventsForms();
            	});
        	
        	getStudies();
			getProjects(false);        	
			

            });
        </script>


<div class="content">
	<div class="main-content">

		<div class="row">

			<div class="col-sm-6 col-md-6">

				<div id="ocStudiesDiv">
					<div class="panel panel-default">
						<div class="panel-heading no-collapse">
							OpenClinica instance: <select id="studySelect">
							<?php
							
foreach ( $openClinicaConfig as $oc ) {
								echo '<option value="' . $oc ['ocName'] . '">' . $oc ['ocName'] . '</option>';
							}
							?>
							</select>
						</div>
						<div id="ocStudiesData">
							<div class="loader"></div>
						</div>
						<div id="studyMessageDiv"></div>
					</div>

				</div>
			</div>

			<div class="col-sm-6 col-md-6">
				<div id="rcProjectsDiv">
					<div class="panel panel-default">
						<div class="panel-heading no-collapse">RedCap projects</div>
						<div id="rcProjectsData">
							<div class="loader"></div>
						</div>
						<div id="projectMessageDiv"></div>
					</div>
				</div>
			</div>


		</div>
		<div class="row">
			<div id="actions" class="col-sm-6 col-md-6">
				<div class="panel panel-default">
					<div class="panel-heading no-collapse">
						Action
						<button id="getStudyMeta"
							class="btn btn-default btn-sm btn-primary">GetStudyData</button>
						<button id="createEventsForms"
							class="btn btn-default btn-sm btn-primary">Create selected events in selected project</button>	
					</div>
					<div id="studyMeta"></div>
				</div>

			</div>

			<div id="pdetails" class="col-sm-6 col-md-6"></div>
		</div>
		<div class="row" id="debug">
<?php

?>
	</div>

	</div>

	<!--main content ends-->
<?php
include 'html_include/html_bottom.php';