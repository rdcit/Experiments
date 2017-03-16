

function callService(){
	var cat=null;
	var text=null;
	fetchWSData(cat,text).done(function(data){
		alert(data);
	});
}

function fetchWSData(category,word) {

	var urlToService = "http://bio-lark.org:8080/biolark/annotate?text=The long bones are shortened, the metaphyses flared, cupped and irregular but the density and structure of the bones is grossly normal.&dataSource=Human Phenotype Ontology";

	return jQuery.ajax({
        type: "POST",
        url: urlToService,
        async: true,
        dataType: "json",
        timeout: 4000,
        success: function(data){
        	//process pedigree data here and populate versions
        	//alert(data.data);
        },
        error: function(jqXHR, textStatus, errorThrown) {
        	  console.log(textStatus, errorThrown);
        	  document.write("Cross-Origin Request Blocked: The Same Origin Policy disallows reading the remote resource at http://bio-lark.org:8080/biolark/annotate?text=The%20long%20bones%20are%20shortened,%20the%20metaphyses%20flared,%20cupped%20and%20irregular%20but%20the%20density%20and%20structure%20of%20the%20bones%20is%20grossly%20normal.&dataSource=Human%20Phenotype%20Ontology. (Reason: CORS header 'Access-Control-Allow-Origin' missing).");
        	}
        
    });		
}