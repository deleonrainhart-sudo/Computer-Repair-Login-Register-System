// Search the rows currently shown in the table. PHP still handles saving records.
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-table-search]').forEach((search) => {
    const table = document.getElementById(search.dataset.tableSearch);
    if (!table) return;
    const rows = [...table.querySelectorAll('tbody tr[data-search-row]')];
    const noMatch = table.querySelector('tbody tr[data-no-match]');
    search.addEventListener('input', () => {
      const query = search.value.trim().toLocaleLowerCase();
      let shown = 0;
      rows.forEach((row) => {
        const matches = row.textContent.toLocaleLowerCase().includes(query);
        row.hidden = !matches;
        if (matches) shown++;
      });
      if (noMatch) noMatch.hidden = shown !== 0;
    });
  });
});
