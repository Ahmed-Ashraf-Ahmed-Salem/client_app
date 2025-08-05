package com.ahmed_ashraf.clientapplication.dto;

import com.fasterxml.jackson.annotation.JsonInclude;
import com.fasterxml.jackson.annotation.JsonProperty;
import lombok.*;

import java.util.List;

@Setter
@Getter
@Data
@AllArgsConstructor
@NoArgsConstructor
@Builder
//@JsonInclude(JsonInclude.Include.NON_NULL)
public class DeleteRequestDTO {
 //   @JsonProperty("username")
    private String username;
 //   @JsonProperty("password")
    private String password;
    private List<SerialNationalDTO> records;
}

