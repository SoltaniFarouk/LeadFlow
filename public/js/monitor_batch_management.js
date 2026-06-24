document.addEventListener("DOMContentLoaded", () => {
  const table = document.querySelector("table");
  const headers = table.querySelectorAll("th");

  headers.forEach((header, index) => {
    let asc = true; // track sort direction per column

    header.style.cursor = "pointer"; // indicate clickable

    header.addEventListener("click", () => {
      sortTable(table, index, asc);

      // reset indicators
      headers.forEach((h) => (h.innerText = h.innerText.replace(/ ▲| ▼/g, "")));
      header.innerText += asc ? " ▲" : " ▼";

      asc = !asc; // toggle for next click
    });
  });

  function sortTable(table, columnIndex, asc = true) {
    const rows = Array.from(table.querySelectorAll("tr:nth-child(n+2)")); // skip header

    // filter only rows with enough cells
    const validRows = rows.filter((row) => row.cells[columnIndex]);

    // check if column is numeric
    const isNumeric = validRows.every(
      (row) => !isNaN(row.cells[columnIndex].innerText.trim())
    );

    validRows.sort((a, b) => {
      const aText = a.cells[columnIndex].innerText.trim();
      const bText = b.cells[columnIndex].innerText.trim();

      let comparison = 0;
      if (isNumeric) {
        comparison = Number(aText) - Number(bText);
      } else {
        comparison = aText.localeCompare(bText, undefined, {
          numeric: true,
          sensitivity: "base",
        });
      }

      return asc ? comparison : -comparison;
    });

    // append sorted rows back
    validRows.forEach((row) => table.appendChild(row));
  }
});

function open_addLotToVicidial(
  selectDataBase,
  selectNameTable,
  phoneCode,
  postalCode,
  numberUse,
  idLot,
  idUserConnecte
) {
  console.log(selectDataBase);
  console.log(selectNameTable);
  console.log(phoneCode);
  console.log(postalCode);
  console.log(idUserConnecte);

  window.open(
    "batch_management/add_batch_to_vicidial.php?var1=" +
      selectDataBase +
      "&var2=" +
      selectNameTable +
      "&var3=" +
      phoneCode +
      "&var4=" +
      postalCode +
      "&var5=" +
      numberUse +
      "&var6=" +
      idLot +
      "&var7=" +
      idUserConnecte,
    "nom_de_ma_popup",
    "menubar=no, scrollbars=no, top=200, left=40%, width=700, height=600"
  );
} // 👈 make sure this closing brace exists

function open_downloadAsText(
  selectDataBase,
  selectNameTable,
  phoneCode,
  postalCode,
  numberUse,
  idLot
) {
  console.log(selectDataBase);
  console.log(selectNameTable);
  console.log(phoneCode);
  console.log(postalCode);

  window.open(
    "batch_management/download_as_text.php?var1=" +
      selectDataBase +
      "&var2=" +
      selectNameTable +
      "&var3=" +
      phoneCode +
      "&var4=" +
      postalCode +
      "&var5=" +
      numberUse +
      "&var6=" +
      idLot,
    "nom_de_ma_popup",
    "menubar=no, scrollbars=no, top=200, left=40%, width=700, height=600"
  );
} // 👈 make sure this closing brace exists

function open_historyBatch(idLot, idUserConnecte) {
  console.log(idLot);
  console.log(idUserConnecte);

  window.open(
    "batch_management/history_batch.php?var1=" +
      idLot +
      "&var2=" +
      idUserConnecte,
    "nom_de_ma_popup",
    "menubar=no, scrollbars=no, top=200, left=40%, width=1400, height=600"
  );
}

// function open add Lot To Vicidial Without 404
function open_addLotToVicidialWithout404(
  selectDataBase,
  selectNameTable,
  phoneCode,
  postalCode,
  numberUse,
  idLot,
  idUserConnecte
) {
  console.log(selectDataBase);
  console.log(selectNameTable);
  console.log(phoneCode);
  console.log(postalCode);
  console.log(idUserConnecte);

  window.open(
    "batch_management/add_batch_w404_to_vicidial.php?var1=" +
      selectDataBase +
      "&var2=" +
      selectNameTable +
      "&var3=" +
      phoneCode +
      "&var4=" +
      postalCode +
      "&var5=" +
      numberUse +
      "&var6=" +
      idLot +
      "&var7=" +
      idUserConnecte,
    "nom_de_ma_popup",
    "menubar=no, scrollbars=no, top=200, left=40%, width=700, height=600"
  );
}
