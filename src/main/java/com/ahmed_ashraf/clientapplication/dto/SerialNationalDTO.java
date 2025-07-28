package com.ahmed_ashraf.clientapplication.dto;

import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.Getter;
import lombok.Setter;

@Setter
@Getter
@Data
@AllArgsConstructor

public class SerialNationalDTO {
    private Long serial;
    private String nationalno;
}
