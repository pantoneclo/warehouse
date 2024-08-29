import React, { useEffect, useRef } from 'react';
import JsBarcode from 'jsbarcode';

const BarcodeGenerator = ({ value }) => {
    const barcodeRef = useRef(null);

    useEffect(() => {
        if (barcodeRef.current) {
            JsBarcode(barcodeRef.current, value, {
                format: 'CODE128', // You can change the format as needed
                lineColor: '#000',
                width: 2,
                height: 100,
                displayValue: true
            });
        }
    }, [value]);

    return (
        <div>
            <svg ref={barcodeRef}></svg>
        </div>
    );
};

export default BarcodeGenerator;
