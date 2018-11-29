  // Version 1.0 
  // Released 8/6/2018
  // Mikey Ling



function export_table_to_csv(html, filename) {
	var csv = [];
	var rows = document.querySelectorAll("table tr"); 

	for (var i = 0; i < rows.length; i++){
		var row = [], cols = rows[i].querySelectorAll("td, th");

		for (var j = 0; j < cols.length; j++){
			row.push(cols[j].innerText); 
		}
		if (i == 0){
			csv.push(row.join(",,,"));
		}
		else{
			csv.push(row.join(",")); 
		}	
	}

	//Download CSV
	download_csv(csv.join("\n"), filename);
}

function download_csv(csv, filename) {
	var csvFile; 
	var downloadLink;

	csvFile = new Blob([csv], {type:"text/csv"});

	downloadLink = document.createElement("a"); 

	downloadLink.download = filename; 

	downloadLink.href = window.URL.createObjectURL(csvFile); 

	downloadLink.style.display = "none"; 

	document.body.appendChild(downloadLink); 

	downloadLink.click(); 

}

document.querySelector("#export_button").addEventListener("click", function() {
	var html = document.querySelector("table").outerHTML; 
	export_table_to_csv(html, "compare_boms.csv");
});
