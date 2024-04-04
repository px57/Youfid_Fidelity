/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
if (typeof billing == 'undefined') billing = {};
if (!billing.util) billing.util = {};

billing.util += {    
    isNullOrEmpty: function(str){
        if(str === null)
            return true;
        else{
            str = trim(str);
            if(str == "")
                return true;
        }
        return false;
    },
    
    validContact: function(){
        var valid = false;
        var errors = new Array();
        if(this.isNullOrEmpty(this.trim($("#companyName").val()))){
            var error = {
                errorFieldId: "companyName",
                errorContainerId: "companyNameError",
                errorMessage: "This field is required"
            };
            errors.push(error);
        }
                
        if(this.isNullOrEmpty(this.trim($("#email").val()))){
            var error = {
                errorFieldId: "email",
                errorContainerId: "emailError",
                errorMessage: "This field is required"
            };
            errors.push(error);
        }
        
        if(this.isNullOrEmpty(this.trim($("#address").val()))){
            var error = {
                errorFieldId: "address",
                errorContainerId: "addressError",
                errorMessage: "This field is required"
            };
            errors.push(error);
        }
        
        if(this.isNullOrEmpty(this.trim($("#city").val()))){
            var error = {
                errorFieldId: "city",
                errorContainerId: "cityError",
                errorMessage: "This field is required"
            };
            errors.push(error);
        }
        
        if(this.isNullOrEmpty(this.trim($("#country").val()))){
            var error = {
                errorFieldId: "country",
                errorContainerId: "countryError",
                errorMessage: "This field is required"
            };
            errors.push(error);
        }
        
        if(errors)
        $(errors).each(function(error){
            $("#"+error.errorContainerId).html(error.errorMessage);
        });
    }
    
    
}