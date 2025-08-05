package com.ahmed_ashraf.clientapplication.dto;

import com.fasterxml.jackson.annotation.JsonProperty;
import lombok.Data;
import lombok.Getter;
import lombok.Setter;

@Getter
@Setter
@Data
public class DeletedRecordDTO {

    @JsonProperty("serial")
    private long serial;

    @JsonProperty("nationalno")
    private String nationalno;

    @JsonProperty("emp_status")
    private String emp_status;

    @JsonProperty("OFF_BRANCH_CODE")
    private String offBranchCode;

    @JsonProperty("OFF_CODE")
    private String offCode;

    @JsonProperty("APPDATE")
    private String appDate;

    @JsonProperty("LASTUPDATE")
    private String lastUpdate;
}


/*package com.ahmed_ashraf.clientapplication.dto;

import lombok.Data;

@Data
public class DeletedRecordDTO {
    private Long serial;
    private String nationalno;
    private String OFF_BRANCH_CODE;
    private String OFF_CODE;
    private String APPDATE;
    private String LASTUPDATE;
}

*/