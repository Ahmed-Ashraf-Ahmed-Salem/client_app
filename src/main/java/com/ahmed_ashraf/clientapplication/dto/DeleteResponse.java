package com.ahmed_ashraf.clientapplication.dto;

import com.fasterxml.jackson.annotation.JsonProperty;
import lombok.Data;
import lombok.Getter;
import lombok.Setter;

import java.util.ArrayList;
import java.util.List;

@Getter
@Setter
@Data
public class DeleteResponse {
    private String status;

    @JsonProperty("Message")
    private String Message;

    @JsonProperty("deleted_records")
    private List<DeletedRecordDTO> deleted_records = new ArrayList<>();
}

