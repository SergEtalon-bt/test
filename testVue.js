const producted = Vue.createApp({});
producted.component('product-edit', {
    props: {
        data: Object,
    },
    data() {
        let resData = JSON.parse(this.data);
        let name = '';

        let arrSuppliersDisplay = {};
        for (let key in resData.product.suppliers) {
            name = resData.product.suppliers[key].id;
            arrSuppliersDisplay[name] = resData.product.suppliers[key];
        }

        let arrSuppliersList = {};
        arrSuppliersList.default = {'id': 'default', 'name': 'Выбирете поставщика'};
        for (let key in resData.suppliers) {
            if (arrSuppliersDisplay.hasOwnProperty(resData.suppliers[key].id)) {
                delete resData.suppliers[key];
            } else {
                name = resData.suppliers[key].id;
                arrSuppliersList[name] = resData.suppliers[key];
            }
            // console.log('supplier');
            // console.log(resData.suppliers[key]);
        }

        console.log('Смотрим data');
        console.log(arrSuppliersDisplay);
        console.log('Смотрим data2');
        console.log(arrSuppliersList);

        return {
            product: resData.product,
            brands: resData.brands,
            categories: resData.categories,
            suppliersList: arrSuppliersList,
            suppliersDisplay: arrSuppliersDisplay,
            openListSuppliers: false,
            supplierId: 'default',
        }
    },
    methods: {
        test() {
            console.log(this.suppliersDisplay);
        },
        addSupplier() {
            if (this.supplierId != 'default') {
                this.suppliersDisplay[this.supplierId] = this.suppliersList[this.supplierId];
                delete this.suppliersList[this.supplierId];
            }
            this.supplierId = 'default';
            this.openListSuppliers = false;
        },
        delSupplier(id) {
            console.log(id);
            this.suppliersList[id] = this.suppliersDisplay[id];
            delete this.suppliersDisplay[id];
            this.openListSuppliers = false;
        },
        chooseSupplier() {
            console.log(this.suppliersDisplay);
            console.log(this.suppliersList);
            this.openListSuppliers = !this.openListSuppliers;
        }
    },
    template: `
            <form :action="'/admin/products/?action=edit&id=' + product.id" method="post"
                  enctype="multipart/form-data" id="form_edit_product" class="mb-3">

                <div class=" row mt-2">
                    <div class="col">
                        <input type="hidden" name="form_edit_id" v-bind:value="product.id">
                        <label for="form_edit_name" class="form-label">Название продукта</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="form_edit_name" name="form_edit_name"
                                   v-bind:value="product.name">
                        </div>
                        
                        <label for="form_edit_brand" class="form-label">Брэнд</label>
                        <select class="form-select mb-3" id="form_edit_brand" name="form_edit_brand" v-model="product.brand.id">                                                
                                <option v-for="brand in brands" v-bind:value="brand.id">{{ brand.name }}</option>
                        </select>
    
                        <label for="form_edit_category" class="form-label">Категория</label>
                        <select class="form-select mb-3" id="form_edit_category" name="form_edit_category" v-model="product.category.id">
                            <option v-for="category in categories" v-bind:value="category.id">{{ category.name }}</option>
                        </select>
                    </div>
                </div>
                
                
                <template v-for="supplier in suppliersDisplay">
                    <div class="row my-2">
                        <div class="col">
                            <h4>{{ supplier.name }}</h4>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <input type="hidden" v-bind:name="'form_edit_suppliers[' + supplier.id + '][id]'" v-bind:value="supplier.id">                                                    
                        <label class="col-2 form-label" v-bind:for="'form_edit_supplier_kod' + supplier.id" >
                            код: 
                        </label>
                        <div class="col-10">
                            <input type="text" class="form-control" v-bind:id="'form_edit_supplier_kod' + supplier.id" 
                                v-bind:name="'form_edit_suppliers[' + supplier.id + '][kod]'" 
                                v-bind:value="supplier.kod">
                        </div>                        
                    </div>
                    <template v-if="supplier.warehouses.length > 0">
                        <div class="row my-2">
                            <div class="col">
                                <h5>Склады</h5>
                            </div>
                        </div>
                        <template v-for="warehouse in supplier.warehouses">                            
                            <div class="row my-2">
                                <label class="col-2 form-label" v-bind:for="'form_edit_warehouse_' + warehouse.id" >
                                    {{ warehouse.name }}
                                </label>
                                <div class="col-10 w-25">
                                    <input type="text" class="form-control" v-bind:id="'form_edit_warehouse_' + warehouse.id" 
                                        v-bind:name="'form_edit_suppliers[' + supplier.id + '][warehouses][' + warehouse.id + ']'" 
                                        v-bind:value="warehouse.quantity">
                                </div>       
                            </div>
                        </template>
                    </template>
                    
                    <template v-if="supplier.price">
                        <div class="row my-2">
                            <div class="col">
                                <h5>Цены</h5>
                            </div>
                        </div>                                           
                            <div class="row my-2">
                                <label class="col-2 form-label" for="'form_edit_price_price" >
                                    Закупочная
                                </label>
                                <div class="col-10 w-25">
                                    <input type="text" class="form-control" id="'form_edit_price_price" 
                                        v-bind:name="'form_edit_suppliers[' + supplier.id + '][price][price]'" 
                                        v-bind:value="supplier.price.price">
                                </div>
                            </div>
                            <div class="row my-2">
                                <label class="col-2 form-label" for="'form_edit_price_rrc" >
                                    РРЦ
                                </label>
                                <div class="col-10 w-25">
                                    <input type="text" class="form-control" id="'form_edit_price_rrc" 
                                        v-bind:name="'form_edit_suppliers[' + supplier.id + '][price][rrc]'" 
                                        v-bind:value="supplier.price.rrc">
                                </div>       
                            </div>
                    </template>
                    
                    <div class="row">
                        <div class="col">
                            <div class="m-3">
                                <a class="btn btn-danger" @click="delSupplier(supplier.id)">Удалить поставщика</a>
                            </div>                            
                        </div>
                    </div>
                </template>
                
                <template v-if="Object.keys(suppliersList).length > 1">
                    <div class="row">
                        <div class="col">                    
                            <a class="btn btn-info w-50 m-3" @click="chooseSupplier">
                              Добавить поставщика
                            </a>
                        </div>
                    </div>
                </template>                    
                    
                <template v-if="openListSuppliers">                    
                    <select  class="form-select w-50" v-model="supplierId" @change="addSupplier">
                        <option v-for="supplier in suppliersList" :value="supplier.id">{{ supplier.name }}</option>
                    </select>
                </template>
                
                <div class="row">
                    <div class="col text-end">
                        <input type="submit" class="btn btn-primary pull-right" name="button_edit_product" value="Изменить продукт">
                    </div>
                </div>
            </form>
		`
})
producted.mount('#templateproduct');
