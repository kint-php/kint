export default class Table {
    static sort(table, column) {
        const curSort = table.dataset.kintTableSort;
        const order = parseInt(curSort) === column ? -1 : 1;
        const tbody = table.tBodies[0];

        [...tbody.rows]
            .sort(function (a, b) {
                a = a.cells[column].textContent.trim().toLocaleLowerCase();
                b = b.cells[column].textContent.trim().toLocaleLowerCase();

                let diff = 0;

                // In lieu of natsort we just sort all numbers before strings
                if (!isNaN(a) && !isNaN(b)) {
                    a = parseFloat(a);
                    b = parseFloat(b);
                    diff = a - b;
                } else if (isNaN(a) && !isNaN(b)) {
                    diff = 1;
                } else if (isNaN(b) && !isNaN(a)) {
                    diff = -1;
                } else {
                    diff = ('' + a).localeCompare('' + b);
                }

                return diff * order;
            })
            .forEach((el) => tbody.appendChild(el));

        if (order < 0) {
            table.dataset.kintTableSort = null;
        } else {
            table.dataset.kintTableSort = column;
        }
    }
}
