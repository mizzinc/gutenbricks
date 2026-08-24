

export function insertContextMenu() {
  // Select the target <div> element by its ID
  var targetDiv = document.getElementById("bricks-builder-context-menu");

  // Check if the targetDiv is not null
  if (targetDiv) {
    // Create a <ul> element
    var ulElement = document.createElement("ul");

    // Create the first <li> element with class "exportgutenberg sep"
    var liElement1 = document.createElement("li");
    liElement1.className = "exportgutenberg sep";

    // Create the second <li> element with class "exportgutenberg" and text content
    var liElement2 = document.createElement("li");
    liElement2.className = "exportgutenberg";
    liElement2.textContent = "Export as Gutenberg block";

    // Append the <li> elements to the <ul>
    ulElement.appendChild(liElement1);
    ulElement.appendChild(liElement2);

    // Append the <ul> to the target <div>
    targetDiv.appendChild(ulElement);
  } else {
    console.log("Target div not found");
  }
}