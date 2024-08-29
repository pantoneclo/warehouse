let objAttrLstFrmApi = singleProduct && Object.entries(singleProduct[0]?.attributes.attributes)
                    .reduce((result, [key, value]) => {
                        result[key] = value.map((item) => ({ label: item, value: item }));
                        return result;
                    }, {});
